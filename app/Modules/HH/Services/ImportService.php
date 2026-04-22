<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function __construct(
        private BudgetYearService $budgetYearService,
        private AuditService $auditService,
    ) {}

    /**
     * Parse and import a CSV file into the given budget year.
     *
     * Returns a result array with keys:
     *   imported  int    – number of positions successfully created
     *   skipped   int    – number of rows skipped (zero-amount or blank name)
     *   errors    array  – list of error strings for rows that failed
     *   warnings  array  – non-fatal notices (e.g. new cost center / account auto-created)
     */
    public function importCsv(UploadedFile $file, BudgetYear $budgetYear, User $actor): array
    {
        $content = file_get_contents($file->getRealPath());

        // Detect and convert encoding (Windows-1252 / Latin-1 CSV files are common)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // Strip UTF-8 BOM if present
        $content = ltrim($content, "\xEF\xBB\xBF");

        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if (empty($lines)) {
            return $this->result(0, 0, ['Die Datei ist leer.'], []);
        }

        // Parse header row
        $header = $this->parseCsvLine(array_shift($lines));
        $header = array_map(fn($h) => mb_strtolower(trim($h)), $header);

        $required = ['kostenstelle', 'sachkonto', 'sachkontoname', 'name', 'hhjahr', 'brutto'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                return $this->result(0, 0, ["Pflicht-Spalte '{$col}' fehlt im CSV-Header."], []);
            }
        }

        $colIndex = array_flip($header);

        // Get or create the active version for the budget year
        $activeVersion = BudgetYearVersion::where('budget_year_id', $budgetYear->id)
            ->where('is_active', true)
            ->first();

        if (!$activeVersion) {
            return $this->result(0, 0, ['Keine aktive Version für dieses Haushaltsjahr gefunden.'], []);
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $warnings = [];

        DB::transaction(function () use (
            $lines, $colIndex, $budgetYear, $activeVersion, $actor,
            &$imported, &$skipped, &$errors, &$warnings
        ) {
            foreach ($lines as $lineNum => $line) {
                $row = $this->parseCsvLine($line);

                if (count($row) < 2) {
                    $skipped++;
                    continue;
                }

                $get = fn(string $key) => isset($colIndex[$key]) ? trim($row[$colIndex[$key]] ?? '') : '';

                $costCenterNumber = $get('kostenstelle');
                $accountNumber    = $get('sachkonto');
                $accountName      = $get('sachkontoname');
                $projectName      = $get('name');
                $description      = isset($colIndex['beschreibung']) ? trim($row[$colIndex['beschreibung']] ?? '') : '';
                $hhJahr           = $get('hhjahr');
                $bruttoRaw        = $get('brutto');

                // Skip rows without a position name
                if ($projectName === '') {
                    $skipped++;
                    continue;
                }

                // Parse amount
                $amount = $this->parseGermanAmount($bruttoRaw);

                // Skip zero-amount rows silently (e.g. "- €" placeholders)
                if ($amount == 0.0) {
                    $skipped++;
                    continue;
                }

                // Determine recurring / start_year
                $isRecurring = (mb_strtolower($hhJahr) === 'jährlich' || mb_strtolower($hhJahr) === 'jahrlich');
                $startYear   = $isRecurring ? $budgetYear->year : (int) $hhJahr;

                if (!$isRecurring && ($startYear < 2000 || $startYear > 2100)) {
                    $errors[] = 'Zeile ' . ($lineNum + 2) . ' ("' . $projectName . '"): Ungültiges Jahr "' . $hhJahr . '".';
                    continue;
                }

                // Resolve or create CostCenter
                $costCenter = CostCenter::firstOrCreate(
                    ['number' => $costCenterNumber],
                    ['name' => $costCenterNumber, 'is_active' => true]
                );

                if ($costCenter->wasRecentlyCreated) {
                    $warnings[] = "Kostenstelle {$costCenterNumber} wurde neu angelegt.";
                }

                // Resolve or create Account
                $accountType = $this->detectAccountType($accountNumber);
                $account = Account::firstOrCreate(
                    ['number' => $accountNumber],
                    ['name' => $accountName ?: $accountNumber, 'type' => $accountType, 'is_active' => true]
                );

                if ($account->wasRecentlyCreated) {
                    $warnings[] = "Sachkonto {$accountNumber} ({$accountName}) wurde neu angelegt als {$accountType}.";
                } elseif ($account->name !== $accountName && $accountName !== '') {
                    // Update name if it changed
                    $account->update(['name' => $accountName]);
                }

                BudgetPosition::create([
                    'budget_year_version_id' => $activeVersion->id,
                    'cost_center_id'         => $costCenter->id,
                    'account_id'             => $account->id,
                    'project_name'           => $projectName,
                    'description'            => $description ?: null,
                    'amount'                 => $amount,
                    'start_year'             => $startYear,
                    'end_year'               => null,
                    'is_recurring'           => $isRecurring,
                    'priority'               => 'mittel',
                    'category'               => 'freiwillige Leistung',
                    'status'                 => 'geplant',
                    'created_by'             => $actor->id,
                ]);

                $imported++;
            }
        });

        return $this->result($imported, $skipped, $errors, $warnings);
    }

    /**
     * Detect account type (investiv/konsumtiv) from the account number.
     *
     * German municipal accounting convention:
     *   - Accounts starting with 0 (01xxxx, 08xxxx) → investiv (capital expenditure)
     *   - All others → konsumtiv (operating expenditure)
     */
    private function detectAccountType(string $accountNumber): string
    {
        return preg_match('/^0[0-9]/', $accountNumber) ? 'investiv' : 'konsumtiv';
    }

    /**
     * Parse a German-formatted currency string to float.
     *
     * Examples: " 55.000,00 € " → 55000.0
     *           " -   € "       → 0.0
     *           "3.600,00 €"    → 3600.0
     */
    private function parseGermanAmount(string $raw): float
    {
        // Strip currency symbols, spaces, dashes used as zero placeholders
        $cleaned = preg_replace('/[€\s]/', '', $raw);

        // Handle dash / em-dash as zero
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '–' || preg_match('/^-+$/', $cleaned)) {
            return 0.0;
        }

        // Remove thousands separator (.) and replace decimal comma with dot
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    /**
     * Parse a single semicolon-delimited CSV line, respecting quoted fields.
     */
    private function parseCsvLine(string $line): array
    {
        // str_getcsv handles quoted fields properly
        return str_getcsv($line, ';', '"');
    }

    private function result(int $imported, int $skipped, array $errors, array $warnings): array
    {
        return compact('imported', 'skipped', 'errors', 'warnings');
    }
}
