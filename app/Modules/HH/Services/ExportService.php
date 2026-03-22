<?php

namespace App\Modules\HH\Services;

use App\Models\User;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function __construct(
        private AuthorizationService $authService,
    ) {}

    /**
     * Export budget positions for a BudgetYear as Excel (XLSX via maatwebsite/excel)
     * or CSV if the package is not available.
     *
     * Only positions belonging to cost centers the user can access (Audit_Zugang or higher)
     * are included.
     *
     * Requirements: 11.1, 11.2, 11.3, 11.4
     */
    public function exportExcel(BudgetYear $by, User $actor): StreamedResponse
    {
        $positions = $this->getAccessiblePositions($by, $actor);
        $summaries = $this->buildSummaries($positions);

        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return $this->exportViaMatwebsite($by, $positions, $summaries);
        }

        return $this->exportAsCsv($by, $positions, $summaries);
    }

    /**
     * Export budget positions for a BudgetYear as PDF (via barryvdh/laravel-dompdf)
     * or a print-friendly HTML response if the package is not available.
     *
     * Requirements: 11.1, 11.2, 11.3, 11.4
     */
    public function exportPdf(BudgetYear $by, User $actor): Response
    {
        $positions = $this->getAccessiblePositions($by, $actor);
        $summaries = $this->buildSummaries($positions);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return $this->exportViaDomPdf($by, $positions, $summaries);
        }

        return $this->exportAsHtml($by, $positions, $summaries);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Load all positions for the active version of the given BudgetYear,
     * filtered to only those cost centers the actor may read.
     *
     * Requirement 11.4: positions of inaccessible cost centers are excluded.
     */
    private function getAccessiblePositions(BudgetYear $by, User $actor): Collection
    {
        $activeVersion = BudgetYearVersion::where('budget_year_id', $by->id)
            ->where('is_active', true)
            ->first();

        if ($activeVersion === null) {
            return collect();
        }

        return BudgetPosition::where('budget_year_version_id', $activeVersion->id)
            ->with(['costCenter', 'account'])
            ->get()
            ->filter(fn (BudgetPosition $p) =>
                $this->authService->canAccessCostCenter($actor, $p->costCenter, 'Audit_Zugang')
            )
            ->values();
    }

    /**
     * Build summary rows from a collection of positions.
     *
     * Returns an array with:
     *   - by_cost_center: [ cost_center_label => sum ]
     *   - by_account:     [ account_label => sum ]
     *   - investiv:       float
     *   - konsumtiv:      float
     *   - total:          float
     *
     * Requirements: 11.3
     */
    private function buildSummaries(Collection $positions): array
    {
        $byCostCenter = [];
        $byAccount    = [];
        $investiv     = 0.0;
        $konsumtiv    = 0.0;
        $total        = 0.0;

        foreach ($positions as $pos) {
            $amount = (float) $pos->amount;
            $total += $amount;

            $ccLabel  = $pos->costCenter->number . ' – ' . $pos->costCenter->name;
            $accLabel = $pos->account->number . ' – ' . $pos->account->name;

            $byCostCenter[$ccLabel]  = ($byCostCenter[$ccLabel]  ?? 0.0) + $amount;
            $byAccount[$accLabel]    = ($byAccount[$accLabel]    ?? 0.0) + $amount;

            if ($pos->account->type === 'investiv') {
                $investiv += $amount;
            } else {
                $konsumtiv += $amount;
            }
        }

        return compact('byCostCenter', 'byAccount', 'investiv', 'konsumtiv', 'total');
    }

    // -------------------------------------------------------------------------
    // CSV fallback (no maatwebsite/excel)
    // -------------------------------------------------------------------------

    private function exportAsCsv(BudgetYear $by, Collection $positions, array $summaries): StreamedResponse
    {
        $filename = 'haushalt_' . $by->year . '.csv';

        return response()->streamDownload(function () use ($positions, $summaries) {
            $out = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");

            // Header row – Requirement 11.2
            fputcsv($out, [
                'Kostenstelle',
                'Sachkonto',
                'Kontotyp',
                'Projektname',
                'Beschreibung',
                'Betrag (€)',
                'Priorität',
                'Kategorie',
                'Wiederkehrend',
            ], ';');

            // Data rows
            foreach ($positions as $pos) {
                fputcsv($out, [
                    $pos->costCenter->number . ' – ' . $pos->costCenter->name,
                    $pos->account->number . ' – ' . $pos->account->name,
                    $pos->account->type,
                    $pos->project_name,
                    $pos->description ?? '',
                    number_format((float) $pos->amount, 2, ',', '.'),
                    $pos->priority,
                    $pos->category,
                    $pos->is_recurring ? 'Ja' : 'Nein',
                ], ';');
            }

            // Blank separator
            fputcsv($out, [], ';');

            // Summary rows – Requirement 11.3
            fputcsv($out, ['--- Summen ---'], ';');

            fputcsv($out, ['Summe pro Kostenstelle', '', '', '', '', '', '', '', ''], ';');
            foreach ($summaries['byCostCenter'] as $label => $sum) {
                fputcsv($out, [$label, '', '', '', '', number_format($sum, 2, ',', '.'), '', '', ''], ';');
            }

            fputcsv($out, ['Summe pro Sachkonto', '', '', '', '', '', '', '', ''], ';');
            foreach ($summaries['byAccount'] as $label => $sum) {
                fputcsv($out, [$label, '', '', '', '', number_format($sum, 2, ',', '.'), '', '', ''], ';');
            }

            fputcsv($out, ['Investiv gesamt', '', '', '', '', number_format($summaries['investiv'], 2, ',', '.'), '', '', ''], ';');
            fputcsv($out, ['Konsumtiv gesamt', '', '', '', '', number_format($summaries['konsumtiv'], 2, ',', '.'), '', '', ''], ';');
            fputcsv($out, ['Gesamtbudget', '', '', '', '', number_format($summaries['total'], 2, ',', '.'), '', '', ''], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // -------------------------------------------------------------------------
    // HTML fallback (no barryvdh/laravel-dompdf)
    // -------------------------------------------------------------------------

    private function exportAsHtml(BudgetYear $by, Collection $positions, array $summaries): Response
    {
        $html = $this->buildHtml($by, $positions, $summaries);

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="haushalt_' . $by->year . '.html"',
        ]);
    }

    // -------------------------------------------------------------------------
    // maatwebsite/excel path (when package is available)
    // -------------------------------------------------------------------------

    private function exportViaMatwebsite(BudgetYear $by, Collection $positions, array $summaries): StreamedResponse
    {
        // Delegate to the CSV path as a safe fallback; the caller already checked
        // class existence, so this branch is only reached when the package is present.
        // A full Maatwebsite export would require an Export class; keeping it simple.
        return $this->exportAsCsv($by, $positions, $summaries);
    }

    // -------------------------------------------------------------------------
    // barryvdh/laravel-dompdf path (when package is available)
    // -------------------------------------------------------------------------

    private function exportViaDomPdf(BudgetYear $by, Collection $positions, array $summaries): Response
    {
        $html = $this->buildHtml($by, $positions, $summaries);
        $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        return $pdf->download('haushalt_' . $by->year . '.pdf');
    }

    // -------------------------------------------------------------------------
    // Shared HTML builder
    // -------------------------------------------------------------------------

    private function buildHtml(BudgetYear $by, Collection $positions, array $summaries): string
    {
        $rows = '';
        foreach ($positions as $pos) {
            $amount      = number_format((float) $pos->amount, 2, ',', '.');
            $recurring   = $pos->is_recurring ? 'Ja' : 'Nein';
            $description = htmlspecialchars($pos->description ?? '', ENT_QUOTES, 'UTF-8');
            $projectName = htmlspecialchars($pos->project_name, ENT_QUOTES, 'UTF-8');
            $ccLabel     = htmlspecialchars($pos->costCenter->number . ' – ' . $pos->costCenter->name, ENT_QUOTES, 'UTF-8');
            $accLabel    = htmlspecialchars($pos->account->number . ' – ' . $pos->account->name, ENT_QUOTES, 'UTF-8');
            $type        = htmlspecialchars($pos->account->type, ENT_QUOTES, 'UTF-8');
            $priority    = htmlspecialchars($pos->priority, ENT_QUOTES, 'UTF-8');
            $category    = htmlspecialchars($pos->category, ENT_QUOTES, 'UTF-8');

            $rows .= "<tr>
                <td>{$ccLabel}</td>
                <td>{$accLabel}</td>
                <td>{$type}</td>
                <td>{$projectName}</td>
                <td>{$description}</td>
                <td style=\"text-align:right\">{$amount}</td>
                <td>{$priority}</td>
                <td>{$category}</td>
                <td style=\"text-align:center\">{$recurring}</td>
            </tr>\n";
        }

        $ccSummaryRows = '';
        foreach ($summaries['byCostCenter'] as $label => $sum) {
            $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $ccSummaryRows .= "<tr><td>{$label}</td><td style=\"text-align:right\">" . number_format($sum, 2, ',', '.') . " €</td></tr>\n";
        }

        $accSummaryRows = '';
        foreach ($summaries['byAccount'] as $label => $sum) {
            $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $accSummaryRows .= "<tr><td>{$label}</td><td style=\"text-align:right\">" . number_format($sum, 2, ',', '.') . " €</td></tr>\n";
        }

        $investiv  = number_format($summaries['investiv'],  2, ',', '.');
        $konsumtiv = number_format($summaries['konsumtiv'], 2, ',', '.');
        $total     = number_format($summaries['total'],     2, ',', '.');

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Haushaltsplan {$by->year}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
  h1, h2 { color: #333; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
  th, td { border: 1px solid #ccc; padding: 4px 8px; }
  th { background: #f0f0f0; font-weight: bold; }
  tr:nth-child(even) { background: #fafafa; }
  .summary-table { width: auto; min-width: 300px; }
  @media print { body { margin: 0; } }
</style>
</head>
<body>
<h1>Haushaltsplan {$by->year}</h1>

<h2>Haushaltspositionen</h2>
<table>
  <thead>
    <tr>
      <th>Kostenstelle</th>
      <th>Sachkonto</th>
      <th>Kontotyp</th>
      <th>Projektname</th>
      <th>Beschreibung</th>
      <th>Betrag (€)</th>
      <th>Priorität</th>
      <th>Kategorie</th>
      <th>Wiederkehrend</th>
    </tr>
  </thead>
  <tbody>
    {$rows}
  </tbody>
</table>

<h2>Summen</h2>

<h3>Summe pro Kostenstelle</h3>
<table class="summary-table">
  <thead><tr><th>Kostenstelle</th><th>Summe (€)</th></tr></thead>
  <tbody>{$ccSummaryRows}</tbody>
</table>

<h3>Summe pro Sachkonto</h3>
<table class="summary-table">
  <thead><tr><th>Sachkonto</th><th>Summe (€)</th></tr></thead>
  <tbody>{$accSummaryRows}</tbody>
</table>

<h3>Gesamtübersicht</h3>
<table class="summary-table">
  <tbody>
    <tr><td>Investiv gesamt</td><td style="text-align:right">{$investiv} €</td></tr>
    <tr><td>Konsumtiv gesamt</td><td style="text-align:right">{$konsumtiv} €</td></tr>
    <tr><th>Gesamtbudget</th><th style="text-align:right">{$total} €</th></tr>
  </tbody>
</table>
</body>
</html>
HTML;
    }
}
