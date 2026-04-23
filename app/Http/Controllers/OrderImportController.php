<?php

namespace App\Http\Controllers;

use App\Models\AccountCode;
use App\Models\CostCenter;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderImportController extends Controller
{
    public function index()
    {
        $batches = Order::whereNotNull('import_batch_id')
            ->selectRaw('import_batch_id, import_source, COUNT(*) as count, SUM(price_gross) as total, MIN(created_at) as imported_at')
            ->groupBy('import_batch_id', 'import_source')
            ->orderByDesc('imported_at')
            ->get();

        return view('orders.import', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->parseCsv($request->file('file')->path());

        if (empty($rows)) {
            return back()->with('error', 'Die CSV-Datei konnte nicht gelesen werden oder enthält keine Daten.');
        }

        $batchId  = 'fibo_' . now()->format('Ymd_His') . '_' . Str::random(4);
        $source   = 'Fibo CSV';

        // Preload lookups
        $costCenters  = CostCenter::pluck('id', 'number');
        $accountCodes = AccountCode::pluck('id', 'code');

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            try {
                $subject = $this->cleanDescription($row['Beschreibung'] ?? '', $row['Sachkontoname'] ?? '');
                if (empty($subject)) {
                    $skipped++;
                    continue;
                }

                $amount     = $this->parseAmount($row['Betrag (MW)'] ?? '0');
                $orderDate  = $this->parseDate($row['Buchungsdatum'] ?? '');
                if (!$orderDate) {
                    $skipped++;
                    continue;
                }

                $costCenterCode = trim($row['Kostenstelle Code'] ?? '');
                $accountCodeNr  = trim($row['Sachkontonr.'] ?? '');
                $belegnr        = trim($row['Belegnr.'] ?? '');
                $belegart       = trim($row['Belegart'] ?? '');
                $buyerUsername  = trim($row['Benutzer-ID'] ?? '');

                $bemerkungen = $belegnr ? "Belegnr.: {$belegnr}" . ($belegart ? " ({$belegart})" : '') : null;

                Order::create([
                    'subject'          => $subject,
                    'quantity'         => 1,
                    'price_gross'      => $amount,
                    'order_date'       => $orderDate,
                    'status'           => 6, // angeordnet (bereits bezahlt)
                    'buyer_username'   => $buyerUsername ?: null,
                    'cost_center_id'   => $costCenters[$costCenterCode] ?? null,
                    'account_code_id'  => $accountCodes[$accountCodeNr] ?? null,
                    'bemerkungen'      => $bemerkungen,
                    'budget_year'      => (int) substr($orderDate, 0, 4),
                    'import_batch_id'  => $batchId,
                    'import_source'    => $source,
                ]);

                $imported++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        $msg = "{$imported} Einträge importiert (Batch: {$batchId})";
        if ($skipped > 0) {
            $msg .= ", {$skipped} übersprungen.";
        }

        return redirect()->route('orders.import')->with('success', $msg);
    }

    public function destroy(string $batchId)
    {
        $count = Order::where('import_batch_id', $batchId)->count();

        if ($count === 0) {
            return redirect()->route('orders.import')->with('error', 'Import-Batch nicht gefunden.');
        }

        Order::where('import_batch_id', $batchId)->delete();

        return redirect()->route('orders.import')
            ->with('success', "{$count} Einträge des Imports \"{$batchId}\" wurden gelöscht.");
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $content = file_get_contents($path);

        // Remove UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Convert encoding if needed
        $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", str_replace("\r\n", "\n", str_replace("\r", "\n", $content)));
        $lines = array_filter(array_map('trim', $lines));

        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines), ';');
        $rows    = [];

        foreach ($lines as $line) {
            if (empty($line)) continue;
            $values = str_getcsv($line, ';');
            if (count($values) !== count($headers)) continue;
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    private function cleanDescription(string $desc, string $accountName): string
    {
        $desc = trim($desc);
        // Strip trailing ", Sachkontoname" suffix that Infoma adds
        if ($accountName && str_ends_with($desc, ', ' . $accountName)) {
            $desc = substr($desc, 0, -(strlen(', ' . $accountName)));
        }
        // Also strip leading space
        $desc = trim($desc);
        // If description is now empty or equals just the account name, use account name
        return $desc ?: $accountName;
    }

    private function parseAmount(string $raw): float
    {
        // German format: 1.234,56 → 1234.56
        $clean = str_replace('.', '', trim($raw));
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }
}
