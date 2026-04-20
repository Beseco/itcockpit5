<?php

namespace App\Services;

use App\Models\Applikation;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ApplikationExportService
{
    private function buildQuery(array $filters)
    {
        $search                   = $filters['search']                    ?? '';
        $filterAbteilungId        = $filters['filter_abteilung_id']       ?? '';
        $filterBaustein           = $filters['filter_baustein']           ?? '';
        $filterAdminUserId        = $filters['filter_admin_user_id']      ?? '';
        $filterOhneVerantwortlich = !empty($filters['filter_ohne_verantwortlich']);
        $filterConfidentiality    = $filters['filter_confidentiality']    ?? '';
        $filterIntegrity          = $filters['filter_integrity']          ?? '';
        $filterAvailability       = $filters['filter_availability']       ?? '';
        $filterOffeneRevision     = !empty($filters['filter_offene_revision']);

        $query = Applikation::with(['adminUser', 'verantwortlichAdUser', 'abteilung'])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('einsatzzweck', 'LIKE', "%{$search}%")
                  ->orWhere('sg', 'LIKE', "%{$search}%")
                  ->orWhere('hersteller', 'LIKE', "%{$search}%");
            });
        }

        if ($filterAbteilungId !== '')     $query->where('abteilung_id', $filterAbteilungId);
        if ($filterBaustein !== '')        $query->where('baustein', $filterBaustein);
        if ($filterAdminUserId === 'none') {
            $query->whereNull('admin_user_id');
        } elseif ($filterAdminUserId !== '') {
            $query->where('admin_user_id', $filterAdminUserId);
        }
        if ($filterOhneVerantwortlich) $query->whereNull('verantwortlich_ad_user_id');
        if ($filterConfidentiality !== '') $query->where('confidentiality', $filterConfidentiality);
        if ($filterIntegrity !== '')       $query->where('integrity', $filterIntegrity);
        if ($filterAvailability !== '')    $query->where('availability', $filterAvailability);
        if ($filterOffeneRevision)         $query->whereNotNull('revision_date')->where('revision_date', '<=', now()->toDateString());

        return $query;
    }

    // -------------------------------------------------------------------------
    // XLSX
    // -------------------------------------------------------------------------

    public function exportXlsx(array $filters): Response
    {
        $apps     = $this->buildQuery($filters)->get();
        $filename = 'applikationen_' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = $this->buildSpreadsheet($apps);
        $tempFile    = tempnam(sys_get_temp_dir(), 'applikationen_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function buildSpreadsheet($apps): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Applikationen');

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5)->setHeader(0.2)->setFooter(0.2);
        $sheet->getHeaderFooter()->setOddFooter('&L&8Erstellt am ' . now()->format('d.m.Y') . '&R&8Seite &P von &N');

        $sheet->getColumnDimension('A')->setWidth(36);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(9);
        $sheet->getColumnDimension('H')->setWidth(9);
        $sheet->getColumnDimension('I')->setWidth(9);
        $sheet->getColumnDimension('J')->setWidth(14);

        $row = 1;

        // Titelzeile
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->setCellValue('A' . $row, 'Applikationen – Stand: ' . now()->format('d.m.Y'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row += 2;

        // Spaltenheader
        $headerRow = $row;
        $headers   = ['Name', 'Sachgebiet', 'Baustein', 'Administrator', 'Verantwortlicher', 'Hersteller', 'Vertr.', 'Integr.', 'Verfüg.', 'Revision'];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue(chr(ord('A') + $i) . $row, $label);
        }
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        $schutzbedarf = Applikation::SCHUTZBEDARF;

        foreach ($apps as $idx => $app) {
            $values = [
                $app->name,
                optional($app->abteilung)->anzeigename ?? $app->sg ?? '',
                $app->baustein ?? '',
                optional($app->adminUser)->name ?? '',
                optional($app->verantwortlichAdUser)->anzeigename ?? '',
                $app->hersteller ?? '',
                ($schutzbedarf[$app->confidentiality] ?? $app->confidentiality) . ' (' . $app->confidentiality . ')',
                ($schutzbedarf[$app->integrity]       ?? $app->integrity)       . ' (' . $app->integrity . ')',
                ($schutzbedarf[$app->availability]    ?? $app->availability)    . ' (' . $app->availability . ')',
                $app->revision_date ? $app->revision_date->format('d.m.Y') : '',
            ];

            foreach ($values as $i => $val) {
                $sheet->setCellValue(chr(ord('A') + $i) . $row, $val);
            }

            $bgColor = $idx % 2 === 0 ? 'FFFFFFFF' : 'FFF9FAFB';
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                'font'      => ['size' => 9, 'color' => ['argb' => 'FF111827']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
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

    public function exportPdf(array $filters): Response
    {
        $apps     = $this->buildQuery($filters)->get();
        $filename = 'applikationen_' . now()->format('Y-m-d') . '.pdf';
        $tz       = config('app.timezone', 'Europe/Berlin');
        $datetime = now($tz)->format('d.m.Y, H:i') . ' Uhr';
        $date     = now($tz)->format('d.m.Y');
        $html     = $this->buildPdfHtml($apps, $datetime, $date);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape');
        $pdf->render();

        $dom    = $pdf->getDomPDF();
        $canvas = $dom->getCanvas();
        $w      = $canvas->get_width();
        $h      = $canvas->get_height();

        $canvas->page_script(function ($pageNum, $pageCount, $cv, $fm) use ($w, $h, $datetime, $date) {
            $bold   = $fm->getFont('DejaVu Sans', 'bold');
            $normal = $fm->getFont('DejaVu Sans', 'normal');

            $cv->filled_rectangle(0, 0, $w, 30, [0.118, 0.106, 0.294]);
            $cv->filled_rectangle(0, 30, $w, 2,  [0.388, 0.251, 0.796]);
            $cv->text(10,  5, 'IT Cockpit',                                        $bold,   13, [1.0,   1.0,   1.0]);
            $cv->text(106, 10, "\xe2\x80\x93 Ihr zentrales IT-Management-Tool",   $normal,  7.5, [0.647, 0.706, 0.988]);
            $cv->text($w - 180,  4, 'APPLIKATIONEN',                               $bold,    9, [0.878, 0.902, 1.0]);
            $cv->text($w - 180, 16, 'Exportiert am ' . $datetime,                 $normal,  6.5, [0.506, 0.549, 0.973]);

            $cv->filled_rectangle(0, $h - 24, $w, 24, [0.945, 0.957, 0.976]);
            $cv->line(0, $h - 24, $w, $h - 24, [0.780, 0.824, 0.996], 0.5);
            $cv->text(12, $h - 15,
                "IT Cockpit  \xc2\xb7  Applikationen  \xc2\xb7  Stand " . $date,
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

    private function buildPdfHtml($apps, string $datetime, string $date): string
    {
        $total        = $apps->count();
        $schutzbedarf = Applikation::SCHUTZBEDARF;

        $tbody = '';
        foreach ($apps as $idx => $app) {
            $name       = htmlspecialchars($app->name);
            $sachgebiet = htmlspecialchars(optional($app->abteilung)->anzeigename ?? $app->sg ?? '');
            $baustein   = htmlspecialchars($app->baustein ?? '');
            $admin      = htmlspecialchars(optional($app->adminUser)->name ?? '');
            $verantw    = htmlspecialchars(optional($app->verantwortlichAdUser)->anzeigename ?? '');
            $hersteller = htmlspecialchars($app->hersteller ?? '');
            $c          = $app->confidentiality;
            $i          = $app->integrity;
            $a          = $app->availability;
            $revision   = $app->revision_date ? $app->revision_date->format('d.m.Y') : '';
            $even       = $idx % 2 === 0 ? '' : ' class="even"';

            $cClass = $c === 'C' ? 'sb-c' : ($c === 'B' ? 'sb-b' : 'sb-a');
            $iClass = $i === 'C' ? 'sb-c' : ($i === 'B' ? 'sb-b' : 'sb-a');
            $aClass = $a === 'C' ? 'sb-c' : ($a === 'B' ? 'sb-b' : 'sb-a');

            $revClass = '';
            if ($revision && $app->revision_date <= now()) {
                $revClass = ' class="overdue"';
            }

            $tbody .= "<tr{$even}>";
            $tbody .= "<td>{$name}</td>";
            $tbody .= "<td>" . ($sachgebiet ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($baustein   ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($admin      ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($verantw    ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td>" . ($hersteller ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "<td class=\"center\"><span class=\"{$cClass}\">{$c}</span></td>";
            $tbody .= "<td class=\"center\"><span class=\"{$iClass}\">{$i}</span></td>";
            $tbody .= "<td class=\"center\"><span class=\"{$aClass}\">{$a}</span></td>";
            $tbody .= "<td class=\"center\"{$revClass}>" . ($revision ?: '<span class="empty">—</span>') . "</td>";
            $tbody .= "</tr>\n";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Applikationen - IT Cockpit</title>
<style>
  @page { margin: 50pt 28pt 34pt 28pt; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8px;
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
    font-size: 7px; padding: 3px 4px; font-weight: bold;
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  th.center, td.center { text-align: center; }

  td { padding: 2px 4px; border-bottom: 1px solid #E5E7EB; font-size: 7.5px; vertical-align: middle; }
  tr.even td { background: #F9FAFB; }
  .empty { color: #9CA3AF; }
  .overdue { color: #DC2626; font-weight: bold; }

  .sb-a { background:#D1FAE5; color:#065F46; padding:1px 4px; border-radius:3px; font-size:7px; }
  .sb-b { background:#FEF3C7; color:#92400E; padding:1px 4px; border-radius:3px; font-size:7px; }
  .sb-c { background:#FEE2E2; color:#991B1B; padding:1px 4px; border-radius:3px; font-size:7px; }
</style>
</head>
<body>

<div class="content-header">
  <h1>Applikationen</h1>
  <div class="sub">Stand: {$date} &nbsp;&middot;&nbsp; Exportiert am {$datetime}</div>
</div>

<div class="stats">
  <span class="chip">{$total} Applikationen gesamt</span>
</div>

<table>
  <thead>
    <tr>
      <th style="width:18%">Name</th>
      <th style="width:11%">Sachgebiet</th>
      <th style="width:9%">Baustein</th>
      <th style="width:13%">Administrator</th>
      <th style="width:13%">Verantwortlicher</th>
      <th style="width:13%">Hersteller</th>
      <th class="center" style="width:7%">Vertr.</th>
      <th class="center" style="width:7%">Integr.</th>
      <th class="center" style="width:7%">Verfüg.</th>
      <th class="center" style="width:10%">Revision</th>
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
