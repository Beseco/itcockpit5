<?php

namespace App\Services;

use App\Models\Aufgabe;
use App\Models\AufgabeZuweisung;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class AufgabenExportService
{
    /**
     * Lädt Aufgaben entsprechend der aktiven Filter.
     * Gibt ein Array von ['aufgabe' => Aufgabe, 'depth' => int] zurück.
     */
    private function loadData(array $filters, User $actor): array
    {
        $search    = $filters['search']    ?? '';
        $gruppeId  = $filters['gruppe_id'] ?? '';
        $adminId   = $filters['admin_id']  ?? '';
        $nurEigene = (bool) ($filters['nur_eigene'] ?? false);
        $sortDir   = ($filters['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $isFiltered = $search !== '' || $gruppeId !== '' || $adminId !== '' || $nurEigene;

        if ($isFiltered) {
            $aufgaben = Aufgabe::with(['zuweisungen.gruppe', 'zuweisungen.admin', 'zuweisungen.stellvertreter', 'parent'])
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($gruppeId, fn($q) => $q->whereHas('zuweisungen', fn($q2) => $q2->where('gruppe_id', $gruppeId)))
                ->when($adminId, fn($q) => $q->whereHas('zuweisungen', fn($q2) => $q2->where('admin_user_id', $adminId)))
                ->when($nurEigene, fn($q) => $q->whereHas('zuweisungen', function ($q2) use ($actor) {
                    $q2->where('admin_user_id', $actor->id)
                       ->orWhere('stellvertreter_user_id', $actor->id);
                }))
                ->orderBy('name', $sortDir)
                ->get();

            return $aufgaben->map(fn($a) => ['aufgabe' => $a, 'depth' => 0])->all();
        }

        // Ungefilterter Baum – mit allen Zuweisungen bis Tiefe 4
        $roots = Aufgabe::with([
            'zuweisungen.gruppe', 'zuweisungen.admin', 'zuweisungen.stellvertreter',
            'children.zuweisungen.gruppe', 'children.zuweisungen.admin', 'children.zuweisungen.stellvertreter',
            'children.children.zuweisungen.gruppe', 'children.children.zuweisungen.admin', 'children.children.zuweisungen.stellvertreter',
            'children.children.children.zuweisungen.gruppe', 'children.children.children.zuweisungen.admin', 'children.children.children.zuweisungen.stellvertreter',
            'children.children.children.children.zuweisungen.gruppe', 'children.children.children.children.zuweisungen.admin', 'children.children.children.children.zuweisungen.stellvertreter',
        ])->whereNull('parent_id')
          ->orderBy('sort_order')->orderBy('name')
          ->get();

        $result = [];
        $flatten = function ($items, int $depth) use (&$flatten, &$result) {
            foreach ($items as $aufgabe) {
                $result[] = ['aufgabe' => $aufgabe, 'depth' => $depth];
                if ($aufgabe->children->isNotEmpty()) {
                    $flatten($aufgabe->children->sortBy([['sort_order', 'asc'], ['name', 'asc']]), $depth + 1);
                }
            }
        };
        $flatten($roots, 0);

        return $result;
    }

    private function formatGruppen(Aufgabe $aufgabe): string
    {
        return $aufgabe->zuweisungen->pluck('gruppe.name')->filter()->join(', ');
    }

    private function formatAdmins(Aufgabe $aufgabe): string
    {
        return $aufgabe->zuweisungen->pluck('admin.name')->filter()->join(', ');
    }

    private function formatStellvertreter(Aufgabe $aufgabe): string
    {
        return $aufgabe->zuweisungen->pluck('stellvertreter.name')->filter()->join(', ');
    }

    // -------------------------------------------------------------------------
    // XLSX
    // -------------------------------------------------------------------------

    public function exportXlsx(array $filters, User $actor): Response
    {
        $rows     = $this->loadData($filters, $actor);
        $filename = 'aufgaben_' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = $this->buildSpreadsheet($rows);
        $tempFile    = tempnam(sys_get_temp_dir(), 'aufgaben_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function buildSpreadsheet(array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aufgaben');

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5)->setHeader(0.2)->setFooter(0.2);
        $sheet->getHeaderFooter()->setOddFooter('&L&8Erstellt am ' . now()->format('d.m.Y') . '&R&8Seite &P von &N');

        // Spaltenbreiten
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(28);

        $row = 1;

        // Titelzeile
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->setCellValue('A' . $row, 'Rollen & Aufgaben – Stand: ' . now()->format('d.m.Y'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row += 2; // Leerzeile

        // Spaltenheader
        $headerRow = $row;
        foreach (['Aufgabe', 'Gruppe', 'Verantwortlich', 'Stellvertreter'] as $i => $label) {
            $sheet->setCellValue(chr(ord('A') + $i) . $row, $label);
        }
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // Datenzeilen
        foreach ($rows as $idx => ['aufgabe' => $aufgabe, 'depth' => $depth]) {
            $prefix = $depth > 0 ? str_repeat('   ', $depth) . '— ' : '';
            $values = [
                $prefix . $aufgabe->name,
                $this->formatGruppen($aufgabe),
                $this->formatAdmins($aufgabe),
                $this->formatStellvertreter($aufgabe),
            ];

            foreach ($values as $i => $val) {
                $sheet->setCellValue(chr(ord('A') + $i) . $row, $val);
            }

            $bgColor = $depth === 0 && $idx % 2 === 0 ? 'FFFFFFFF' : ($depth === 0 ? 'FFF9FAFB' : 'FFFAFAFA');
            $bold    = $depth === 0;

            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                'font'      => ['size' => 9, 'bold' => $bold, 'color' => ['argb' => 'FF111827']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1, 'wrapText' => false],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(15);
            $row++;
        }

        $sheet->freezePane('A' . ($headerRow + 1));

        return $spreadsheet;
    }

    // -------------------------------------------------------------------------
    // PDF
    // -------------------------------------------------------------------------

    public function exportPdf(array $filters, User $actor): Response
    {
        $rows     = $this->loadData($filters, $actor);
        $filename = 'aufgaben_' . now()->format('Y-m-d') . '.pdf';
        $tz       = config('app.timezone', 'Europe/Berlin');
        $datetime = now($tz)->format('d.m.Y, H:i') . ' Uhr';
        $date     = now($tz)->format('d.m.Y');
        $html     = $this->buildPdfHtml($rows, $datetime, $date);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');
        $pdf->render();

        $dom    = $pdf->getDomPDF();
        $canvas = $dom->getCanvas();
        $w      = $canvas->get_width();
        $h      = $canvas->get_height();

        $canvas->page_script(function ($pageNum, $pageCount, $cv, $fm) use ($w, $h, $datetime, $date) {
            $bold   = $fm->getFont('DejaVu Sans', 'bold');
            $normal = $fm->getFont('DejaVu Sans', 'normal');

            // Kopfzeile
            $cv->filled_rectangle(0, 0, $w, 30, [0.118, 0.106, 0.294]);
            $cv->filled_rectangle(0, 30, $w, 2,  [0.388, 0.251, 0.796]);
            $cv->text(10,  5, 'IT Cockpit',                                         $bold,   13, [1.0,   1.0,   1.0]);
            $cv->text(106, 10, "\xe2\x80\x93 Ihr zentrales IT-Management-Tool",    $normal,  7.5, [0.647, 0.706, 0.988]);
            $cv->text($w - 152,  4, 'ROLLEN & AUFGABEN',                           $bold,    9, [0.878, 0.902, 1.0]);
            $cv->text($w - 152, 16, 'Exportiert am ' . $datetime,                  $normal,  6.5, [0.506, 0.549, 0.973]);

            // Fusszeile
            $cv->filled_rectangle(0, $h - 24, $w, 24, [0.945, 0.957, 0.976]);
            $cv->line(0, $h - 24, $w, $h - 24, [0.780, 0.824, 0.996], 0.5);
            $cv->text(12, $h - 15,
                "IT Cockpit  \xc2\xb7  Rollen & Aufgaben  \xc2\xb7  Stand " . $date,
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

    private function buildPdfHtml(array $rows, string $datetime, string $date): string
    {
        $total = count($rows);

        $tbody = '';
        foreach ($rows as $idx => ['aufgabe' => $aufgabe, 'depth' => $depth]) {
            $indent  = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . ($depth > 0 ? '&mdash;&nbsp;' : '');
            $name    = htmlspecialchars($aufgabe->name);
            $gruppen = htmlspecialchars($this->formatGruppen($aufgabe));
            $admins  = htmlspecialchars($this->formatAdmins($aufgabe));
            $stv     = htmlspecialchars($this->formatStellvertreter($aufgabe));
            $even    = $idx % 2 === 0 ? '' : ' class="even"';
            $bold    = $depth === 0 ? ' style="font-weight:bold;"' : '';

            $tbody .= "<tr{$even}>";
            $tbody .= "<td{$bold}>{$indent}{$name}</td>";
            $tbody .= "<td>" . ($gruppen ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($admins  ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($stv     ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "</tr>\n";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Rollen &amp; Aufgaben - IT Cockpit</title>
<style>
  @page { margin: 50pt 28pt 34pt 28pt; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #111827;
    margin: 0;
    padding: 0;
  }

  .content-header   { margin: 0 0 8px 0; border-bottom: 1.5px solid #E0E7FF; padding: 0 0 5px 0; }
  .content-header h1 { font-size: 13px; color: #1E1B4B; font-weight: bold; margin: 0; padding: 0; }
  .content-header .sub { font-size: 7px; color: #6B7280; margin: 1px 0 0 0; padding: 0; }

  .stats { margin: 0 0 9px 0; padding: 0; font-size: 7.5px; }
  .chip { display: inline-block; background: #F3F4F6; color: #374151; padding: 1px 7px; border-radius: 3px; margin-right: 5px; }

  table { width: 100%; border-collapse: collapse; margin: 0; padding: 0; }
  th {
    background: #374151; color: #fff; text-align: left;
    font-size: 7.5px; padding: 3px 5px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  th.aufgabe { }
  th.col { width: 20%; }

  td { padding: 2px 5px; border-bottom: 1px solid #E5E7EB; font-size: 8px; vertical-align: middle; }
  tr.even td { background: #F9FAFB; }
  .empty { color: #9CA3AF; }
</style>
</head>
<body>

<div class="content-header">
  <h1>Rollen &amp; Aufgaben</h1>
  <div class="sub">Stand: {$date} &nbsp;&middot;&nbsp; Exportiert am {$datetime}</div>
</div>

<div class="stats">
  <span class="chip">{$total} Aufgaben gesamt</span>
</div>

<table>
  <thead>
    <tr>
      <th class="aufgabe">Aufgabe</th>
      <th class="col">Gruppe</th>
      <th class="col">Verantwortlich</th>
      <th class="col">Stellvertreter</th>
    </tr>
  </thead>
  <tbody>
{$tbody}
  </tbody>
</table>

</body>
</html>
HTML;
    }
}
