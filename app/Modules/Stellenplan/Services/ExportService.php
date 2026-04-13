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

    public function exportXlsx(User $actor): \Symfony\Component\HttpFoundation\Response
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
        $datetime        = now()->format('d.m.Y, H:i') . ' Uhr';
        $date            = now()->format('d.m.Y');
        $html            = $this->buildPdfHtml($gruppen, $ohneGruppe, $canSeeSensitive, $datetime, $date);

        // DomPDF-Default-Rand: 36pt (0.5") auf allen Seiten = ca. 12.7mm ≥ 1cm.
        // setOption() kennt keine Margin-Keys – Canvas-Header muss <36pt bleiben.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');
        $pdf->render();

        $dom    = $pdf->getDomPDF();
        $canvas = $dom->getCanvas();
        $w      = $canvas->get_width();   // A4 portrait ≈ 595 pt
        $h      = $canvas->get_height();  // A4 portrait ≈ 842 pt

        // Kopf- und Fusszeile via DomPDF-Canvas-API: erscheint zuverlässig auf jeder Seite
        $canvas->page_script(function ($pageNum, $pageCount, $cv, $fm) use ($w, $h, $datetime, $date) {
            $bold   = $fm->getFont('DejaVu Sans', 'bold');
            $normal = $fm->getFont('DejaVu Sans', 'normal');

            // === Kopfzeile (30pt Hintergrund + 2pt Akzent = 32pt gesamt < 36pt Standard-Rand) ===
            $cv->filled_rectangle(0, 0, $w, 30, [0.118, 0.106, 0.294]);    // #1E1B4B
            $cv->filled_rectangle(0, 30, $w, 2,  [0.388, 0.251, 0.796]);    // #6366F1 Akzent
            $cv->text(10,  5, 'IT Cockpit',                                          $bold,   13, [1.0,   1.0,   1.0]);
            $cv->text(106, 10, "\xe2\x80\x93 Ihr zentrales IT-Management-Tool",     $normal,  7.5, [0.647, 0.706, 0.988]);
            $cv->text($w - 152,  4, 'STELLENPLAN',                                  $bold,    9, [0.878, 0.902, 1.0]);
            $cv->text($w - 152, 16, 'Exportiert am ' . $datetime,                   $normal,  6.5, [0.506, 0.549, 0.973]);

            // === Fusszeile ===
            $cv->filled_rectangle(0, $h - 24, $w, 24, [0.945, 0.957, 0.976]);       // #F1F5F9
            $cv->line(0, $h - 24, $w, $h - 24, [0.780, 0.824, 0.996], 0.5);
            $cv->text(12, $h - 15,
                "IT Cockpit  \xc2\xb7  Stellenplan  \xc2\xb7  Stand " . $date,
                $normal, 7, [0.392, 0.455, 0.545]);
            $cv->text($w - 82, $h - 15,
                'Seite ' . $pageNum . ' / ' . $pageCount,
                $normal, 7, [0.392, 0.455, 0.545]);
        });

        return response($dom->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildPdfHtml(
        Collection $gruppen,
        Collection $ohneGruppe,
        bool $canSeeSensitive,
        string $datetime,
        string $date
    ): string {
        $besGrSpalte = $canSeeSensitive ? '<th class="bes">Bes.-Gr.</th>' : '';

        $alleStellen  = $gruppen->flatMap->stellen->merge($ohneGruppe);
        $totalStellen = $alleStellen->count();
        $totalFrei    = (int) $alleStellen->sum(fn ($s) => $s->isFrei() ? 100 : max(0, 100 - ($s->belegung ?? 100)));
        $freiCount    = $alleStellen->filter->isFrei()->count();

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

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Stellenplan - IT Cockpit</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #111827;
  }

  /* Inhaltsheader */
  .content-header   { margin-bottom: 8px; border-bottom: 1.5px solid #E0E7FF; padding-bottom: 5px; }
  .content-header h1 { font-size: 13px; color: #1E1B4B; font-weight: bold; }
  .content-header .sub { font-size: 7px; color: #6B7280; margin-top: 1px; }

  /* Statistik-Chips */
  .stats { margin-bottom: 9px; font-size: 7.5px; }
  .chip {
    display: inline-block;
    background: #F3F4F6; color: #374151;
    padding: 1px 7px; border-radius: 3px; margin-right: 5px;
  }
  .chip-warn   { background: #FEF3C7; color: #B45309; }
  .chip-danger { background: #FEE2E2; color: #DC2626; }

  /* Gruppen */
  .group { margin-bottom: 10px; page-break-inside: avoid; }
  .group-header { background: #4338CA; padding: 3px 7px; border-radius: 2px 2px 0 0; }
  .group-header table { width: 100%; border-collapse: collapse; }
  .group-header td { padding: 0; border: none; background: transparent; color: #fff; font-size: 8.5px; font-weight: bold; }
  .group-header .gh-count { text-align: right; font-weight: normal; font-size: 7.5px; color: #C7D2FE; }

  /* Datentabelle */
  table { width: 100%; border-collapse: collapse; }
  th {
    background: #374151; color: #fff; text-align: left;
    font-size: 7.5px; padding: 3px 5px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  th.num { width: 44px; }
  th.bez { }
  th.inh { width: 22%; }
  th.bes { width: 11%; }
  th.pct { width: 8%; text-align: center; }

  td { padding: 2px 5px; border-bottom: 1px solid #E5E7EB; font-size: 8px; vertical-align: middle; }
  td.num { font-family: DejaVu Sans Mono, monospace; color: #6B7280; font-size: 7.5px; }
  td.pct { text-align: center; }
  tr.frei td { background: #FFFBEB; color: #92400E; }
  tr.frei .frei-badge {
    display: inline-block; background: #FDE68A; color: #92400E;
    padding: 0 4px; border-radius: 2px; font-weight: bold; font-size: 7.5px;
  }
  tr.frei td.frei-val { color: #B45309; font-weight: bold; }
  tr.even td { background: #F9FAFB; }
  tr.summe td {
    background: #E0E7FF; color: #1E1B4B; font-weight: bold; font-size: 7.5px;
    border-top: 1.5px solid #818CF8; padding: 3px 5px;
  }
  tr.summe td.pct { text-align: center; }
</style>
</head>
<body>

<div class="content-header">
  <h1>Stellenplan</h1>
  <div class="sub">Stand: {$date} &nbsp;&middot;&nbsp; Exportiert am {$datetime}</div>
</div>

<div class="stats">
  <span class="chip">{$totalStellen} Stellen gesamt</span>
  <span class="chip chip-warn">{$freiCount} unbesetzt</span>
  <span class="chip chip-danger">{$totalFrei}&thinsp;% freie Kapazit&auml;t</span>
</div>

{$gruppenHtml}

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
        foreach ($stellen as $idx => $stelle) {
            $belegt    = $stelle->isFrei() ? 0 : (float) ($stelle->belegung ?? 0);
            $frei      = $stelle->isFrei() ? 100 : max(0, 100 - (float) ($stelle->belegung ?? 100));
            $rowClass  = $stelle->isFrei() ? 'frei' : ($idx % 2 !== 0 ? 'even' : '');
            $freiClass = 'pct' . ($frei >= 50 && !$stelle->isFrei() ? ' frei-val' : ($stelle->isFrei() ? ' frei-val' : ''));

            $nr       = htmlspecialchars($stelle->stellennummer ?? '', ENT_QUOTES, 'UTF-8');
            // Null-Fallback VOR htmlspecialchars trennen – sonst wird '&mdash;' zu '&amp;mdash;'
            $bezRaw   = $stelle->stellenbeschreibung?->bezeichnung;
            $bez      = $bezRaw ? htmlspecialchars($bezRaw, ENT_QUOTES, 'UTF-8') : '&mdash;';
            $inhaber  = $stelle->isFrei()
                ? '<span class="frei-badge">FREI</span>'
                : htmlspecialchars($stelle->stelleninhaber?->name ?? '', ENT_QUOTES, 'UTF-8');
            $belegStr = $belegt > 0 ? number_format($belegt, 0) . '&nbsp;%' : '&mdash;';
            $freiStr  = $frei > 0 ? number_format($frei, 0) . '&nbsp;%' : '&mdash;';

            $besGrRaw  = $stelle->bes_gruppe;
            $besGrCell = $canSeeSensitive
                ? '<td>' . ($besGrRaw ? htmlspecialchars($besGrRaw, ENT_QUOTES, 'UTF-8') : '&mdash;') . '</td>'
                : '';

            $rows .= "<tr class=\"{$rowClass}\">
                <td class=\"num\">{$nr}</td>
                <td>{$bez}</td>
                <td>{$inhaber}</td>
                {$besGrCell}
                <td class=\"pct\">{$belegStr}</td>
                <td class=\"{$freiClass}\">{$freiStr}</td>
            </tr>\n";
        }

        $sumBelegt = number_format($gBelegt, 0) . '&nbsp;%';
        $sumFrei   = number_format($gFrei, 0) . '&nbsp;%';
        $sumCols   = $canSeeSensitive ? 4 : 3;

        return <<<HTML
<div class="group">
  <div class="group-header">
    <table><tr>
      <td>{$name}</td>
      <td style="width:80px;">{$count}&nbsp;Stellen</td>
    </tr></table>
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
