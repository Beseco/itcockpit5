<?php

namespace App\Modules\Schulen\Services;

use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Dienstleistung;
use App\Modules\Schulen\Models\Schule;
use App\Modules\Schulen\Models\SchuleDienstleistung;
use App\Modules\Schulen\Models\SchulTyp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\Response;

class SchulenExportService
{
    // ─────────────────────────────────────────────────────────────────────────
    // On-Demand: gibt Stream-Response zurück
    // ─────────────────────────────────────────────────────────────────────────

    public function exportMatrix(string $format): Response
    {
        return match ($format) {
            'xlsx' => $this->matrixXlsx(),
            'pdf'  => $this->matrixPdf(),
            default => abort(404),
        };
    }

    public function exportDienstleistungen(string $format): Response
    {
        return match ($format) {
            'pdf'  => $this->dienstleistungenPdf(),
            'docx' => $this->dienstleistungenDocx(),
            default => abort(404),
        };
    }

    public function exportSchulenListe(string $format): Response
    {
        return match ($format) {
            'pdf'  => $this->schulenListePdf(),
            'xlsx' => $this->schulenListeXlsx(),
            default => abort(404),
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Nightly: alle 6 Dateien in ein Verzeichnis speichern
    // ─────────────────────────────────────────────────────────────────────────

    public function generateAllToDirectory(string $dir): array
    {
        $files = [];

        $this->saveToFile($this->matrixXlsx(),           "{$dir}/matrix.xlsx");
        $files[] = 'matrix.xlsx';

        $this->saveToFile($this->matrixPdf(),            "{$dir}/matrix.pdf");
        $files[] = 'matrix.pdf';

        $this->saveToFile($this->dienstleistungenPdf(),  "{$dir}/dienstleistungen.pdf");
        $files[] = 'dienstleistungen.pdf';

        $this->saveToFile($this->dienstleistungenDocx(), "{$dir}/dienstleistungen.docx");
        $files[] = 'dienstleistungen.docx';

        $this->saveToFile($this->schulenListePdf(),      "{$dir}/schulen-liste.pdf");
        $files[] = 'schulen-liste.pdf';

        $this->saveToFile($this->schulenListeXlsx(),     "{$dir}/schulen-liste.xlsx");
        $files[] = 'schulen-liste.xlsx';

        return $files;
    }

    private function saveToFile(Response $response, string $path): void
    {
        file_put_contents($path, $response->getContent());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Matrix – Excel
    // ─────────────────────────────────────────────────────────────────────────

    private function matrixXlsx(): Response
    {
        ['schulen' => $schulen, 'dienstleistungen' => $dienste, 'pivots' => $pivots,
         'schulenGruppen' => $schulenGruppen, 'diensteGruppen' => $diensteGruppen,
         'schulTypen' => $schulTypen, 'kategorien' => $kategorien] = $this->loadMatrixData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Matrix');

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);
        $sheet->getHeaderFooter()->setOddFooter('&L&8IT Cockpit · Schulen-Matrix · Stand ' . now()->format('d.m.Y') . '&R&8Seite &P von &N');

        $row = 1;

        // Titel
        $lastCol = 'A';
        $sheet->setCellValue('A1', 'Schulen – Dienstleistungsmatrix · Stand: ' . now()->format('d.m.Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $row = 3;

        // Spalten aufbauen: Spalte A = Dienstleistung, dann pro Schultyp/Schule eine Spalte
        $colMap = []; // schule_id => Spaltenbuchstabe
        $col = 2;    // Spalte B = erste Schule

        $sheet->getColumnDimension('A')->setWidth(30);

        foreach ($schulTypen as $typ) {
            $typSchulen = $schulenGruppen->get($typ->id, collect());
            if ($typSchulen->isEmpty()) continue;

            $startCol = $col;
            foreach ($typSchulen as $schule) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $colMap[$schule->id] = $colLetter;
                $sheet->getColumnDimension($colLetter)->setWidth(14);
                $col++;
            }
            $endCol = $col - 1;

            // Schultyp-Gruppenheader
            $startLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol);
            $endLetter   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol);
            if ($startCol < $endCol) {
                $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
            }
            $sheet->setCellValue("{$startLetter}{$row}", $typ->name);
            $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 8],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Schulnamen-Zeile
        $row++;
        $sheet->setCellValue('A' . $row, 'Dienstleistung');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FF111827']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        foreach ($schulen as $schule) {
            if (!isset($colMap[$schule->id])) continue;
            $c = $colMap[$schule->id];
            $sheet->setCellValue("{$c}{$row}", $schule->name);
            $sheet->getStyle("{$c}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 7, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'textRotation' => 45, 'vertical' => Alignment::VERTICAL_BOTTOM],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(60);
        }

        $headerRow = $row;
        $row++;

        $statusShort = [
            'aktiv'            => 'Aktiv',
            'geplant'          => 'Geplant',
            'nicht_vorhanden'  => '–',
            'nicht_gewuenscht' => 'N. gew.',
            'nicht_moeglich'   => 'N. mög.',
        ];
        $statusColors = [
            'aktiv'            => ['FFBBF7D0', 'FF14532D'],
            'geplant'          => ['FFFEF08A', 'FF713F12'],
            'nicht_vorhanden'  => ['FFFFFFFF', 'FFD1D5DB'],
            'nicht_gewuenscht' => ['FFFED7AA', 'FF7C2D12'],
            'nicht_moeglich'   => ['FFFECACA', 'FF7F1D1D'],
        ];

        $dataStartRow = $row;
        $rowIdx = 0;

        foreach ($kategorien as $kat) {
            $katDienste = $diensteGruppen->get($kat->id, collect());
            if ($katDienste->isEmpty()) continue;

            // Kategorie-Header
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1);
            $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
            $sheet->setCellValue("A{$row}", $kat->name);
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FF1E1B4B']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(14);
            $row++;

            foreach ($katDienste as $dienst) {
                $bgBase = $rowIdx % 2 === 0 ? 'FFFFFFFF' : 'FFF9FAFB';
                $sheet->setCellValue("A{$row}", $dienst->name);
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['size' => 8],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgBase]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(14);

                foreach ($schulen as $schule) {
                    if (!isset($colMap[$schule->id])) continue;
                    $c      = $colMap[$schule->id];
                    $pivot  = $pivots->get($schule->id)?->firstWhere('dienstleistung_id', $dienst->id);
                    $status = $pivot?->status ?? 'nicht_vorhanden';
                    $label  = $statusShort[$status] ?? $status;
                    [$bg, $fg] = $statusColors[$status] ?? ['FFFFFFFF', 'FF374151'];

                    $sheet->setCellValue("{$c}{$row}", $label);
                    $sheet->getStyle("{$c}{$row}")->applyFromArray([
                        'font'      => ['size' => 7, 'color' => ['argb' => $fg]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $row++;
                $rowIdx++;
            }
        }

        // VZE-Spalten am Ende
        $vzeStartCol = $col;
        $vzeIstCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($vzeStartCol);
        $vzeSollCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($vzeStartCol + 1);
        $sheet->getColumnDimension($vzeIstCol)->setWidth(8);
        $sheet->getColumnDimension($vzeSollCol)->setWidth(8);

        $sheet->setCellValue("{$vzeIstCol}{$headerRow}", 'VZE IST');
        $sheet->setCellValue("{$vzeSollCol}{$headerRow}", 'VZE SOLL');
        foreach ([$vzeIstCol, $vzeSollCol] as $vc) {
            $sheet->getStyle("{$vc}{$headerRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 7, 'color' => ['argb' => 'FF1F2937']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $sheet->freezePane('B' . ($headerRow + 1));

        $tempFile = tempnam(sys_get_temp_dir(), 'schulen_matrix_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            'schulen_matrix_' . now()->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Matrix – PDF
    // ─────────────────────────────────────────────────────────────────────────

    private function matrixPdf(): Response
    {
        ['schulen' => $schulen, 'dienstleistungen' => $dienste, 'pivots' => $pivots,
         'diensteGruppen' => $diensteGruppen, 'kategorien' => $kategorien] = $this->loadMatrixData();

        $date     = now()->format('d.m.Y');
        $datetime = now()->format('d.m.Y, H:i') . ' Uhr';
        $html     = view('schulen::exports.matrix', compact(
            'schulen', 'dienste', 'pivots', 'diensteGruppen', 'kategorien', 'date', 'datetime'
        ))->render();

        return $this->renderPdf($html, 'schulen_matrix_' . now()->format('Y-m-d') . '.pdf', 'a3', 'landscape', 'MATRIX');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dienstleistungen – PDF
    // ─────────────────────────────────────────────────────────────────────────

    private function dienstleistungenPdf(): Response
    {
        ['dienste' => $dienste, 'diensteGruppen' => $diensteGruppen, 'kategorien' => $kategorien] = $this->loadDienstleistungenData();
        $date     = now()->format('d.m.Y');
        $datetime = now()->format('d.m.Y, H:i') . ' Uhr';
        $html     = view('schulen::exports.dienstleistungen', compact(
            'dienste', 'diensteGruppen', 'kategorien', 'date', 'datetime'
        ))->render();

        return $this->renderPdf($html, 'schulen_dienstleistungen_' . now()->format('Y-m-d') . '.pdf', 'a4', 'portrait', 'DIENSTLEISTUNGEN');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dienstleistungen – Word
    // ─────────────────────────────────────────────────────────────────────────

    private function dienstleistungenDocx(): Response
    {
        ['diensteGruppen' => $diensteGruppen, 'kategorien' => $kategorien] = $this->loadDienstleistungenData();

        $phpWord = new PhpWord();
        $phpWord->getDefaultFontName('Arial');
        $phpWord->getDefaultFontSize(10);

        $phpWord->addFontStyle('heading1', ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '1E1B4B']);
        $phpWord->addFontStyle('heading2', ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => '374151']);
        $phpWord->addFontStyle('label',    ['name' => 'Arial', 'size' => 8,  'bold' => true, 'color' => '6B7280']);
        $phpWord->addFontStyle('value',    ['name' => 'Arial', 'size' => 9,  'color' => '111827']);
        $phpWord->addParagraphStyle('spaceBefore', ['spaceBefore' => 120, 'spaceAfter' => 60]);

        $section = $phpWord->addSection([
            'paperSize'    => 'A4',
            'orientation'  => 'portrait',
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
        ]);

        // Titel
        $section->addText('Schulen – Dienstleistungen', 'heading1');
        $section->addText('Stand: ' . now()->format('d.m.Y'), ['name' => 'Arial', 'size' => 9, 'color' => '6B7280']);
        $section->addTextBreak(1);

        foreach ($kategorien as $kat) {
            $katDienste = $diensteGruppen->get($kat->id, collect());
            if ($katDienste->isEmpty()) continue;

            $section->addText($kat->name, 'heading2', 'spaceBefore');

            foreach ($katDienste as $dienst) {
                $tableStyle = [
                    'borderSize'  => 1,
                    'borderColor' => 'E5E7EB',
                    'cellMargin'  => 80,
                    'width'       => 9000,
                    'unit'        => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                ];
                $table = $section->addTable($tableStyle);
                $table->addRow();

                // Linke Spalte: Name
                $cell1 = $table->addCell(3000, ['bgColor' => 'F3F4F6']);
                $cell1->addText($dienst->name, ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => '1F2937']);

                // Rechte Spalte: Details
                $cell2 = $table->addCell(6000);
                if ($dienst->beschreibung) {
                    $cell2->addText($dienst->beschreibung, 'value');
                }

                // Stunden/VZE
                $stunden = $dienst->jahresstunden();
                $vze     = $dienst->vzeProSchule();
                $stundenText = '';
                if ($stunden !== null) {
                    $stundenText = number_format($stunden, 1, ',', '.') . ' Std./Jahr';
                    if ($vze !== null) {
                        $stundenText .= ' (' . number_format($vze, 3, ',', '.') . ' VZE/Schule)';
                    }
                }
                if ($stundenText) {
                    $cell2->addText($stundenText, ['name' => 'Arial', 'size' => 8, 'color' => '6B7280']);
                }

                if ($dienst->dokumentation_url) {
                    $cell2->addText('Doku: ' . $dienst->dokumentation_url, ['name' => 'Arial', 'size' => 8, 'color' => '4F46E5']);
                }

                $section->addTextBreak(1);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'schulen_dienste_');
        $tempDocx = $tempFile . '.docx';
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempDocx);

        return response()->download(
            $tempDocx,
            'schulen_dienstleistungen_' . now()->format('Y-m-d') . '.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Schulen-Liste – PDF
    // ─────────────────────────────────────────────────────────────────────────

    private function schulenListePdf(): Response
    {
        $schulen = Schule::with(['schulTyp', 'kontakte', 'dienstleistungen' => function ($q) {
            $q->with('kategorie')->aktiv()->orderBy('sort_order')->orderBy('name');
        }])->orderBy('name')->get();

        $date     = now()->format('d.m.Y');
        $datetime = now()->format('d.m.Y, H:i') . ' Uhr';
        $html     = view('schulen::exports.schulen_liste', compact('schulen', 'date', 'datetime'))->render();

        return $this->renderPdf($html, 'schulen_liste_' . now()->format('Y-m-d') . '.pdf', 'a4', 'portrait', 'SCHULEN-LISTE');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Schulen-Liste – Excel
    // ─────────────────────────────────────────────────────────────────────────

    private function schulenListeXlsx(): Response
    {
        $schulen = Schule::with(['schulTyp', 'kontakte', 'dienstleistungen' => function ($q) {
            $q->aktiv();
        }])->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Schulen');

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);
        $sheet->getHeaderFooter()->setOddFooter('&L&8IT Cockpit · Schulen · Stand ' . now()->format('d.m.Y') . '&R&8Seite &P von &N');

        foreach (['A' => 30, 'B' => 18, 'C' => 18, 'D' => 14, 'E' => 25, 'F' => 8, 'G' => 8, 'H' => 8] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $row = 1;
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Schulen – Übersicht · Stand: ' . now()->format('d.m.Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $row = 3;

        $headers = ['Name', 'Typ', 'Ort', 'Telefon', 'E-Mail', 'Akt. Dienste', 'IST-VZE', 'SOLL-VZE'];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue(chr(ord('A') + $i) . $row, $label);
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF111827']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $headerRow = $row;
        $row++;

        foreach ($schulen as $idx => $schule) {
            $aktivDienste = $schule->dienstleistungen->filter(fn($d) => $d->pivot->status === 'aktiv');
            $istVze = $aktivDienste->sum(function ($d) {
                $stunden = $d->pivot->stunden_override ?? $d->jahresstunden();
                return $stunden !== null ? $stunden / Dienstleistung::VZE_JAHRESSTUNDEN : 0;
            });
            $sollVze = $aktivDienste->sum(fn($d) => $d->vzeProSchule() ?? 0);

            $ersterKontakt = $schule->kontakte->first();
            $telefon  = $schule->telefon ?: ($ersterKontakt?->telefon ?? '');
            $email    = $schule->email   ?: ($ersterKontakt?->email   ?? '');

            $values = [
                $schule->name,
                $schule->typLabel(),
                $schule->adresse(),
                $telefon,
                $email,
                $aktivDienste->count(),
                $istVze  ? round($istVze,  2) : '',
                $sollVze ? round($sollVze, 2) : '',
            ];

            foreach ($values as $i => $val) {
                $sheet->setCellValue(chr(ord('A') + $i) . $row, $val);
            }

            $bgColor = $idx % 2 === 0 ? 'FFFFFFFF' : 'FFF9FAFB';
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                'font'      => ['size' => 9, 'color' => ['argb' => 'FF111827']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(15);
            $row++;
        }

        $sheet->freezePane('A' . ($headerRow + 1));

        $tempFile = tempnam(sys_get_temp_dir(), 'schulen_liste_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            'schulen_liste_' . now()->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Daten laden
    // ─────────────────────────────────────────────────────────────────────────

    private function loadMatrixData(): array
    {
        $schulen = Schule::with('schulTyp')->orderBy('schul_typ_id')->orderBy('sort_order')->orderBy('name')->get();
        $dienste = Dienstleistung::with('kategorie')->aktiv()->orderBy('sort_order')->orderBy('name')->get();

        $pivots = SchuleDienstleistung::whereIn('schule_id', $schulen->pluck('id'))
            ->whereIn('dienstleistung_id', $dienste->pluck('id'))
            ->get()
            ->groupBy('schule_id');

        $kategorien     = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $schulTypen     = SchulTyp::orderBy('sort_order')->orderBy('name')->get();
        $schulenGruppen = $schulen->groupBy('schul_typ_id');
        $diensteGruppen = $dienste->groupBy('dienst_kategorie_id');

        return compact('schulen', 'dienste', 'pivots', 'kategorien', 'schulTypen', 'schulenGruppen', 'diensteGruppen');
    }

    private function loadDienstleistungenData(): array
    {
        $dienste        = Dienstleistung::with('kategorie')->aktiv()->orderBy('sort_order')->orderBy('name')->get();
        $kategorien     = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $diensteGruppen = $dienste->groupBy('dienst_kategorie_id');

        return compact('dienste', 'kategorien', 'diensteGruppen');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF-Renderer (dompdf)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderPdf(string $html, string $filename, string $paper, string $orientation, string $sectionTitle): Response
    {
        $date     = now()->format('d.m.Y');
        $datetime = now()->format('d.m.Y, H:i') . ' Uhr';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper($paper, $orientation);
        $pdf->render();

        $dom    = $pdf->getDomPDF();
        $canvas = $dom->getCanvas();
        $w      = $canvas->get_width();
        $h      = $canvas->get_height();

        $canvas->page_script(function ($pageNum, $pageCount, $cv, $fm) use ($w, $h, $datetime, $date, $sectionTitle) {
            $bold   = $fm->getFont('DejaVu Sans', 'bold');
            $normal = $fm->getFont('DejaVu Sans', 'normal');

            $cv->filled_rectangle(0, 0, $w, 30, [0.118, 0.106, 0.294]);
            $cv->filled_rectangle(0, 30, $w, 2,  [0.388, 0.251, 0.796]);
            $cv->text(10,  5, 'IT Cockpit',                                      $bold,   13, [1.0,   1.0,   1.0]);
            $cv->text(106, 10, "\xe2\x80\x93 Ihr zentrales IT-Management-Tool", $normal,  7.5, [0.647, 0.706, 0.988]);
            $cv->text($w - 180,  4, 'SCHULEN \xe2\x80\x93 ' . $sectionTitle,    $bold,    9, [0.878, 0.902, 1.0]);
            $cv->text($w - 180, 16, 'Exportiert am ' . $datetime,               $normal,  6.5, [0.506, 0.549, 0.973]);

            $cv->filled_rectangle(0, $h - 24, $w, 24, [0.945, 0.957, 0.976]);
            $cv->line(0, $h - 24, $w, $h - 24, [0.780, 0.824, 0.996], 0.5);
            $cv->text(12, $h - 15,
                "IT Cockpit  \xc2\xb7  Schulen  \xc2\xb7  Stand " . $date,
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
}
