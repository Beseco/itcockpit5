<?php

namespace App\Modules\Stellenplan\Services;

use App\Models\Gruppe;
use App\Models\Stelle;
use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Gibt alle Gruppen+Stellen sowie Stellen ohne Gruppe zurück.
     *
     * @return array{gruppen: Collection, ohneGruppe: Collection}
     */
    private function loadData(): array
    {
        $gruppen = Gruppe::with([
            'stellen' => fn ($q) => $q->with(['stellenbeschreibung', 'stelleninhaber'])
                                      ->orderBy('stellennummer'),
        ])->orderBy('name')->get();

        $ohneGruppe = Stelle::whereNull('gruppe_id')
            ->with(['stellenbeschreibung', 'stelleninhaber'])
            ->orderBy('stellennummer')
            ->get();

        return compact('gruppen', 'ohneGruppe');
    }

    // -------------------------------------------------------------------------
    // XLSX
    // -------------------------------------------------------------------------

    public function exportXlsx(User $actor): StreamedResponse
    {
        ['gruppen' => $gruppen, 'ohneGruppe' => $ohneGruppe] = $this->loadData();

        $canSeeSensitive = $actor->can('module.stellenplan.view_sensitive');
        $filename        = 'stellenplan_' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = $this->buildSpreadsheet($gruppen, $ohneGruppe, $canSeeSensitive);

        $tempFile = tempnam(sys_get_temp_dir(), 'stellenplan_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function buildSpreadsheet(Collection $gruppen, Collection $ohneGruppe, bool $canSeeSensitive): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stellenplan');

        // Seitenlayout: Querformat A4
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5)->setHeader(0.2)->setFooter(0.2);
        $sheet->getHeaderFooter()->setOddFooter('&L&8Erstellt am ' . now()->format('d.m.Y') . '&R&8Seite &P von &N');

        // Spalten definieren
        $cols = ['Nr.', 'Bezeichnung', 'Stelleninhaber'];
        if ($canSeeSensitive) {
            $cols[] = 'Bes.-Gr.';
        }
        $cols[]    = 'Belegt %';
        $cols[]    = 'Frei %';
        $totalCols = count($cols);
        $lastLetter = $this->colLetter($totalCols - 1);

        // Spaltenbreiten
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(38);
        $sheet->getColumnDimension('C')->setWidth(28);
        if ($canSeeSensitive) {
            $sheet->getColumnDimension('D')->setWidth(14);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(12);
        } else {
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(12);
        }

        $row = 1;

        // Titel-Zeile
        $sheet->mergeCells('A' . $row . ':' . $lastLetter . $row);
        $sheet->setCellValue('A' . $row, 'Stellenplan – Stand: ' . now()->format('d.m.Y'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;
        $row++; // Leerzeile

        // Spaltenheader-Zeile
        $headerRow = $row;
        foreach ($cols as $i => $label) {
            $cell = $this->colLetter($i) . $row;
            $sheet->setCellValue($cell, $label);
        }
        $sheet->getStyle('A' . $row . ':' . $lastLetter . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // Datenzeilen: Gruppen
        $totalBelegt = 0;
        $totalFrei   = 0;
        $totalCount  = 0;

        foreach ($gruppen as $gruppe) {
            if ($gruppe->stellen->isEmpty()) {
                continue;
            }

            [$row, $gBelegt, $gFrei] = $this->writeGroup(
                $sheet, $row, $gruppe->name, $gruppe->stellen, $canSeeSensitive, $totalCols, $lastLetter
            );

            $totalBelegt += $gBelegt;
            $totalFrei   += $gFrei;
            $totalCount  += $gruppe->stellen->count();

            $row++; // Abstand zwischen Gruppen
        }

        // "Ohne Gruppe"-Abschnitt
        if ($ohneGruppe->isNotEmpty()) {
            [$row, $gBelegt, $gFrei] = $this->writeGroup(
                $sheet, $row, 'Ohne Gruppe', $ohneGruppe, $canSeeSensitive, $totalCols, $lastLetter
            );
            $totalBelegt += $gBelegt;
            $totalFrei   += $gFrei;
            $totalCount  += $ohneGruppe->count();
            $row++;
        }

        // Gesamtsumme
        $sheet->mergeCells('A' . $row . ':' . $this->colLetter($totalCols - 3) . $row);
        $sheet->setCellValue('A' . $row, 'GESAMT  (' . $totalCount . ' Stellen)');
        $sheet->setCellValue($this->colLetter($totalCols - 2) . $row, number_format($totalBelegt, 0) . ' %');
        $sheet->setCellValue($this->colLetter($totalCols - 1) . $row, number_format($totalFrei, 0) . ' %');
        $sheet->getStyle('A' . $row . ':' . $lastLetter . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E1B4B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']],
            ],
        ]);
        $sheet->getStyle($this->colLetter($totalCols - 2) . $row . ':' . $lastLetter . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(18);

        // Einfrieren ab Datenzeile
        $sheet->freezePane('A' . ($headerRow + 1));

        return $spreadsheet;
    }

    /**
     * Schreibt einen Gruppenabschnitt (Header + Zeilen + Summe).
     *
     * @return array{int, float, float} [$nextRow, $gBelegt, $gFrei]
     */
    private function writeGroup(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        string $gruppenName,
        Collection $stellen,
        bool $canSeeSensitive,
        int $totalCols,
        string $lastLetter
    ): array {
        // Gruppenheader
        $sheet->mergeCells('A' . $row . ':' . $lastLetter . $row);
        $sheet->setCellValue('A' . $row, $gruppenName . '  (' . $stellen->count() . ' Stellen)');
        $sheet->getStyle('A' . $row . ':' . $lastLetter . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(16);
        $row++;

        $gBelegt = 0.0;
        $gFrei   = 0.0;

        foreach ($stellen as $idx => $stelle) {
            $belegt = $stelle->isFrei() ? 0.0 : (float) ($stelle->belegung ?? 0);
            $frei   = $stelle->isFrei() ? 100.0 : max(0.0, 100.0 - (float) ($stelle->belegung ?? 100));
            $gBelegt += $belegt;
            $gFrei   += $frei;

            $values = [
                $stelle->stellennummer,
                $stelle->stellenbeschreibung?->bezeichnung ?? '—',
                $stelle->isFrei() ? 'FREI' : ($stelle->stelleninhaber?->name ?? ''),
            ];
            if ($canSeeSensitive) {
                $values[] = $stelle->bes_gruppe ?? '—';
            }
            $values[] = $belegt > 0 ? number_format($belegt, 0) . ' %' : '—';
            $values[] = $frei > 0 ? number_format($frei, 0) . ' %' : '—';

            foreach ($values as $i => $val) {
                $sheet->setCellValue($this->colLetter($i) . $row, $val);
            }

            // Zeilenstil
            $bgColor = $stelle->isFrei() ? 'FFFFF8E1' : ($idx % 2 === 0 ? 'FFFFFFFF' : 'FFF9FAFB');
            $fgColor = $stelle->isFrei() ? 'FFB45309' : 'FF111827';

            $sheet->getStyle('A' . $row . ':' . $lastLetter . $row)->applyFromArray([
                'font'      => ['size' => 9, 'color' => ['argb' => $fgColor]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
            // Belegt/Frei zentriert
            $sheet->getStyle($this->colLetter($totalCols - 2) . $row . ':' . $lastLetter . $row)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getRowDimension($row)->setRowHeight(15);
            $row++;
        }

        // Summen-Zeile
        $sheet->mergeCells('A' . $row . ':' . $this->colLetter($totalCols - 3) . $row);
        $sheet->setCellValue('A' . $row, 'Summe');
        $sheet->setCellValue($this->colLetter($totalCols - 2) . $row, number_format($gBelegt, 0) . ' %');
        $sheet->setCellValue($this->colLetter($totalCols - 1) . $row, number_format($gFrei, 0) . ' %');
        $sheet->getStyle('A' . $row . ':' . $lastLetter . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => [
                'top'    => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF818CF8']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF818CF8']],
            ],
        ]);
        $sheet->getStyle($this->colLetter($totalCols - 2) . $row . ':' . $lastLetter . $row)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(15);
        $row++;

        return [$row, $gBelegt, $gFrei];
    }

    /** Gibt den Spaltenbuchstaben für Index 0 = A, 1 = B, … zurück. */
    private function colLetter(int $index): string
    {
        return chr(ord('A') + $index);
    }

    // -------------------------------------------------------------------------
    // PDF
    // -------------------------------------------------------------------------

    public function exportPdf(User $actor): Response
    {
        ['gruppen' => $gruppen, 'ohneGruppe' => $ohneGruppe] = $this->loadData();

        $canSeeSensitive = $actor->can('module.stellenplan.view_sensitive');
        $filename        = 'stellenplan_' . now()->format('Y-m-d') . '.pdf';
        $html            = $this->buildPdfHtml($gruppen, $ohneGruppe, $canSeeSensitive);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function buildPdfHtml(Collection $gruppen, Collection $ohneGruppe, bool $canSeeSensitive): string
    {
        $besGrSpalte = $canSeeSensitive
            ? '<th class="bes">Bes.-Gr.</th>'
            : '';

        // Gesamtstatistik
        $alleStellen  = $gruppen->flatMap->stellen->merge($ohneGruppe);
        $totalStellen = $alleStellen->count();
        $totalFrei    = (int) $alleStellen->sum(fn ($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
        $freiCount    = $alleStellen->filter->isFrei()->count();

        // Gruppen-Tabellen aufbauen
        $gruppenHtml = '';
        foreach ($gruppen as $gruppe) {
            if ($gruppe->stellen->isEmpty()) {
                continue;
            }
            $gruppenHtml .= $this->buildPdfGroupTable($gruppe->name, $gruppe->stellen, $canSeeSensitive, $besGrSpalte);
        }
        if ($ohneGruppe->isNotEmpty()) {
            $gruppenHtml .= $this->buildPdfGroupTable('Ohne Gruppe', $ohneGruppe, $canSeeSensitive, $besGrSpalte);
        }

        $date = now()->format('d.m.Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Stellenplan</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111827; }

  .header { margin-bottom: 12px; border-bottom: 2px solid #4338CA; padding-bottom: 6px; }
  .header h1 { font-size: 15px; color: #1E1B4B; }
  .header .meta { font-size: 8px; color: #6B7280; margin-top: 2px; }
  .stats { display: flex; gap: 16px; margin-bottom: 10px; font-size: 8px; }
  .stats span { background: #F3F4F6; padding: 2px 8px; border-radius: 3px; }
  .stats .warn { background: #FEF3C7; color: #B45309; }
  .stats .danger { background: #FEE2E2; color: #DC2626; }

  .group { margin-bottom: 14px; page-break-inside: avoid; }
  .group-header {
    background: #4338CA; color: #fff; font-weight: bold; font-size: 9px;
    padding: 4px 8px; border-radius: 2px 2px 0 0;
    display: flex; justify-content: space-between; align-items: center;
  }
  .group-header .g-meta { font-weight: normal; font-size: 8px; opacity: 0.85; }

  table { width: 100%; border-collapse: collapse; }
  th {
    background: #374151; color: #fff; text-align: left; font-size: 8px;
    padding: 3px 6px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em;
  }
  th.num { width: 48px; }
  th.bez { width: 30%; }
  th.inh { width: 22%; }
  th.bes { width: 10%; }
  th.pct { width: 8%; text-align: center; }

  td { padding: 3px 6px; border-bottom: 1px solid #E5E7EB; font-size: 8.5px; vertical-align: middle; }
  td.num { font-family: DejaVu Sans Mono, monospace; color: #6B7280; font-size: 8px; }
  td.pct { text-align: center; }
  tr.frei td { background: #FFFBEB; color: #92400E; }
  tr.frei td.frei-val { color: #B45309; font-weight: bold; }
  tr:not(.frei):not(.summe):nth-child(even) td { background: #F9FAFB; }

  tr.summe td {
    background: #E0E7FF; color: #1E1B4B; font-weight: bold; font-size: 8px;
    border-top: 1.5px solid #818CF8; padding: 3px 6px;
  }
  tr.summe td.pct { text-align: center; }

  .total-box {
    margin-top: 10px; border: 1.5px solid #1E1B4B; border-radius: 3px;
    padding: 6px 10px; background: #EEF2FF; display: inline-block; min-width: 200px;
  }
  .total-box h3 { font-size: 9px; color: #1E1B4B; margin-bottom: 4px; }
  .total-box table { width: auto; }
  .total-box td { font-size: 8.5px; padding: 2px 6px; border: none; background: transparent; }
  .total-box td:last-child { font-weight: bold; }

  .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7px; color: #9CA3AF;
            text-align: right; padding: 2px 8px; border-top: 1px solid #E5E7EB; }
</style>
</head>
<body>

<div class="header">
  <h1>Stellenplan</h1>
  <div class="meta">Stand: {$date}</div>
</div>

<div class="stats">
  <span>{$totalStellen} Stellen gesamt</span>
  <span class="warn">{$freiCount} unbesetzt</span>
  <span class="danger">{$totalFrei}&thinsp;% freie Kapazität gesamt</span>
</div>

{$gruppenHtml}

<div class="footer">IT Cockpit · Stellenplan · Stand {$date}</div>
</body>
</html>
HTML;
    }

    private function buildPdfGroupTable(
        string $name,
        Collection $stellen,
        bool $canSeeSensitive,
        string $besGrSpalte
    ): string {
        $gBelegt = (float) $stellen->sum(fn ($s) => $s->isFrei() ? 0 : ($s->belegung ?? 0));
        $gFrei   = (float) $stellen->sum(fn ($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
        $count   = $stellen->count();
        $name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($stellen as $stelle) {
            $belegt     = $stelle->isFrei() ? 0 : (float) ($stelle->belegung ?? 0);
            $frei       = $stelle->isFrei() ? 100 : max(0, 100 - (float) ($stelle->belegung ?? 100));
            $rowClass   = $stelle->isFrei() ? ' class="frei"' : '';
            $freiClass  = $frei >= 50 ? ' class="pct frei-val"' : ' class="pct"';

            $nr        = htmlspecialchars($stelle->stellennummer ?? '', ENT_QUOTES, 'UTF-8');
            $bez       = htmlspecialchars($stelle->stellenbeschreibung?->bezeichnung ?? '—', ENT_QUOTES, 'UTF-8');
            $inhaber   = $stelle->isFrei()
                ? '<span style="background:#FDE68A;padding:1px 4px;border-radius:2px;font-weight:bold;">FREI</span>'
                : htmlspecialchars($stelle->stelleninhaber?->name ?? '', ENT_QUOTES, 'UTF-8');
            $belegStr  = $belegt > 0 ? number_format($belegt, 0) . '&thinsp;%' : '—';
            $freiStr   = $frei > 0 ? number_format($frei, 0) . '&thinsp;%' : '—';

            $besGrCell = $canSeeSensitive
                ? '<td>' . htmlspecialchars($stelle->bes_gruppe ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
                : '';

            $rows .= "<tr{$rowClass}>
                <td class=\"num\">{$nr}</td>
                <td>{$bez}</td>
                <td>{$inhaber}</td>
                {$besGrCell}
                <td class=\"pct\">{$belegStr}</td>
                <td{$freiClass}>{$freiStr}</td>
            </tr>\n";
        }

        $sumBelegt = number_format($gBelegt, 0) . '&thinsp;%';
        $sumFrei   = number_format($gFrei, 0) . '&thinsp;%';
        $sumCols   = $canSeeSensitive ? 4 : 3;

        return <<<HTML
<div class="group">
  <div class="group-header">
    <span>{$name}</span>
    <span class="g-meta">{$count}&nbsp;Stellen</span>
  </div>
  <table>
    <thead>
      <tr>
        <th class="num">Nr.</th>
        <th class="bez">Bezeichnung</th>
        <th class="inh">Stelleninhaber</th>
        {$besGrSpalte}
        <th class="pct">Belegt</th>
        <th class="pct">Frei</th>
      </tr>
    </thead>
    <tbody>
      {$rows}
      <tr class="summe">
        <td colspan="{$sumCols}">Summe</td>
        <td class="pct">{$sumBelegt}</td>
        <td class="pct">{$sumFrei}</td>
      </tr>
    </tbody>
  </table>
</div>
HTML;
    }
}
