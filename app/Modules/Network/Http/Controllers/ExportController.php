<?php

namespace App\Modules\Network\Http\Controllers;

use App\Modules\Network\Models\Vlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportController
{
    public function exportStream(Request $request)
    {
        return response()->stream(function () {
            ini_set('memory_limit', '256M');

            if (ob_get_level()) {
                ob_end_clean();
            }

            $send = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                flush();
            };

            try {
                $send('progress', ['percent' => 2, 'message' => 'Lade VLANs...']);

                $vlans = Vlan::orderBy('vlan_id')->get();
                $total = $vlans->count();

                $spreadsheet = new Spreadsheet();
                $spreadsheet->getProperties()
                    ->setCreator('IT Cockpit')
                    ->setTitle('Netzwerk-Export')
                    ->setDescription('VLAN- und IP-Übersicht');

                // ── Übersichtsblatt ──────────────────────────────────────────
                $send('progress', ['percent' => 5, 'message' => 'Erstelle Übersicht...']);

                $overview = $spreadsheet->getActiveSheet();
                $overview->setTitle('Übersicht');

                $headers = [
                    'VLAN ID', 'Name', 'Netzwerk', 'Gateway',
                    'DHCP Von', 'DHCP Bis', 'IPs Online', 'IPs Gesamt',
                    'Internes Netz', 'IP-Scan', 'Beschreibung',
                ];
                foreach ($headers as $col => $label) {
                    $overview->getCell([$col + 1, 1])->setValue($label);
                }
                $this->applyHeaderStyle($overview, 'A1:K1');

                foreach ($vlans as $row => $vlan) {
                    $r = $row + 2;
                    $online = $vlan->ipAddresses()->where('is_online', true)->count();
                    $total2  = $vlan->ipAddresses()->count();

                    $overview->getCell([1, $r])->setValue($vlan->vlan_id);
                    $overview->getCell([2, $r])->setValue($vlan->vlan_name);
                    $overview->getCell([3, $r])->setValue($vlan->network_address . '/' . $vlan->cidr_suffix);
                    $overview->getCell([4, $r])->setValue($vlan->gateway ?? '');
                    $overview->getCell([5, $r])->setValue($vlan->dhcp_from ?? '');
                    $overview->getCell([6, $r])->setValue($vlan->dhcp_to ?? '');
                    $overview->getCell([7, $r])->setValue($online);
                    $overview->getCell([8, $r])->setValue($total2);
                    $overview->getCell([9, $r])->setValue($vlan->internes_netz ? 'Ja' : 'Nein');
                    $overview->getCell([10, $r])->setValue($vlan->ipscan ? 'Ja' : 'Nein');
                    $overview->getCell([11, $r])->setValue($vlan->description ?? '');

                    if ($r % 2 === 0) {
                        $this->applyAlternatingRow($overview, "A{$r}:K{$r}");
                    }
                }

                foreach (range('A', 'K') as $col) {
                    $overview->getColumnDimension($col)->setAutoSize(true);
                }
                $this->applyTableBorder($overview, 'A1:K' . ($vlans->count() + 1));
                $overview->setAutoFilter('A1:K1');
                $overview->freezePane('A2');

                // ── Ein Blatt pro VLAN ───────────────────────────────────────
                foreach ($vlans as $index => $vlan) {
                    $percent = (int)(10 + (($index + 1) / $total) * 85);
                    $send('progress', [
                        'percent' => $percent,
                        'message' => "VLAN {$vlan->vlan_id}: {$vlan->vlan_name}",
                    ]);

                    $sheetTitle = $this->sanitizeSheetTitle(
                        'VLAN ' . str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) . ' ' . $vlan->vlan_name
                    );

                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle($sheetTitle);

                    $sheet->setCellValue('A1', 'VLAN ID:');
                    $sheet->setCellValue('B1', $vlan->vlan_id);
                    $sheet->setCellValue('A2', 'Name:');
                    $sheet->setCellValue('B2', $vlan->vlan_name);
                    $sheet->setCellValue('A3', 'Netzwerk:');
                    $sheet->setCellValue('B3', $vlan->network_address . '/' . $vlan->cidr_suffix);
                    $sheet->setCellValue('A4', 'Gateway:');
                    $sheet->setCellValue('B4', $vlan->gateway ?? '-');
                    $sheet->setCellValue('A5', 'DHCP-Bereich:');
                    $sheet->setCellValue('B5', $vlan->dhcp_from
                        ? ($vlan->dhcp_from . ' - ' . $vlan->dhcp_to)
                        : 'Nicht konfiguriert');
                    $sheet->setCellValue('A6', 'Beschreibung:');
                    $sheet->setCellValue('B6', $vlan->description ?? '');

                    $sheet->getStyle('A1:A6')->getFont()->setBold(true);
                    $sheet->getColumnDimension('A')->setWidth(18);
                    $sheet->getColumnDimension('B')->setAutoSize(true);

                    $ipHeaders = ['IP-Adresse', 'DNS-Name', 'MAC-Adresse', 'Status', 'Ping (ms)', 'Zuletzt Online', 'DHCP', 'Kommentar'];
                    foreach ($ipHeaders as $col => $label) {
                        $sheet->getCell([$col + 1, 8])->setValue($label);
                    }
                    $lastCol = 'H';
                    $this->applyHeaderStyle($sheet, "A8:{$lastCol}8");

                    $rowIndex = 9;
                    $vlan->ipAddresses()->orderByRaw('INET_ATON(ip_address)')->chunk(200, function ($ips) use ($sheet, $lastCol, &$rowIndex) {
                        foreach ($ips as $ip) {
                            $r = $rowIndex++;
                            $sheet->getCell([1, $r])->setValue($ip->ip_address);
                            $sheet->getCell([2, $r])->setValue($ip->dns_name ?? '');
                            $sheet->getCell([3, $r])->setValue($ip->mac_address ?? '');
                            $sheet->getCell([4, $r])->setValue($ip->is_online ? 'Online' : 'Offline');
                            $sheet->getCell([5, $r])->setValue($ip->ping_ms ?? '');
                            $sheet->getCell([6, $r])->setValue(
                                $ip->last_online_at ? $ip->last_online_at->format('d.m.Y H:i') : ''
                            );
                            $sheet->getCell([7, $r])->setValue($ip->isInDhcpRange() ? 'Ja' : 'Nein');
                            $sheet->getCell([8, $r])->setValue($ip->comment ?? '');

                            $fill = $ip->is_online ? 'C6EFCE' : 'F2F2F2';
                            $font = $ip->is_online ? '276221' : '666666';
                            $sheet->getStyle([4, $r])->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
                                'font' => ['color' => ['rgb' => $font]],
                            ]);

                            if ($r % 2 === 0) {
                                $this->applyAlternatingRow($sheet, "A{$r}:{$lastCol}{$r}");
                            }
                        }
                    });

                    $lastRow = $rowIndex - 1;
                    if ($lastRow >= 8) {
                        $this->applyTableBorder($sheet, "A8:{$lastCol}{$lastRow}");
                    }
                    foreach (range('A', $lastCol) as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    $sheet->setAutoFilter("A8:{$lastCol}8");
                    $sheet->freezePane('A9');
                }

                // ── Datei speichern ──────────────────────────────────────────
                $send('progress', ['percent' => 97, 'message' => 'Datei wird gespeichert...']);

                $spreadsheet->setActiveSheetIndex(0);

                $dir = storage_path('app/exports');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $token = Str::random(40);
                $path  = $dir . '/' . $token . '.xls';

                $writer = new Xls($spreadsheet);
                $writer->save($path);

                $send('done', ['token' => $token]);

            } catch (\Throwable $e) {
                $send('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function exportDownload(Request $request, string $token)
    {
        if (!preg_match('/^[a-zA-Z0-9]{40}$/', $token)) {
            abort(404);
        }

        $path = storage_path('app/exports/' . $token . '.xls');

        if (!file_exists($path)) {
            abort(404);
        }

        $filename = 'netzwerk_export_' . now()->format('Y-m-d_His') . '.xls';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ])->deleteFileAfterSend(true);
    }

    private function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '1D4ED8'],
                ],
            ],
        ]);
    }

    private function applyAlternatingRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EFF6FF');
    }

    private function applyTableBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D1D5DB'],
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '6B7280'],
                ],
            ],
        ]);
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\/\\\?\*\[\]:]/', ' ', $title);
        return mb_substr($title, 0, 31);
    }
}
