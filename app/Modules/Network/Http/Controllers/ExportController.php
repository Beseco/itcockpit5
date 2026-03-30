<?php

namespace App\Modules\Network\Http\Controllers;

use App\Modules\Network\Models\Vlan;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExportController
{
    public function export(Request $request)
    {
        $vlans = Vlan::with(['ipAddresses' => function ($q) {
            $q->orderByRaw('INET_ATON(ip_address)');
        }])->orderBy('vlan_id')->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('IT Cockpit')
            ->setTitle('Netzwerk-Export')
            ->setDescription('VLAN- und IP-Übersicht');

        // ── Blatt 1: VLAN-Übersicht ──────────────────────────────────────────
        $overview = $spreadsheet->getActiveSheet();
        $overview->setTitle('Übersicht');

        $headers = [
            'VLAN ID', 'Name', 'Netzwerk', 'Gateway',
            'DHCP Von', 'DHCP Bis', 'IPs Online', 'IPs Gesamt',
            'Internes Netz', 'IP-Scan', 'Beschreibung',
        ];

        foreach ($headers as $col => $label) {
            $cell = $overview->getCellByColumnAndRow($col + 1, 1);
            $cell->setValue($label);
        }

        $this->applyHeaderStyle($overview, 'A1:K1');

        foreach ($vlans as $row => $vlan) {
            $r = $row + 2;
            $ips = $vlan->ipAddresses;
            $online = $ips->where('is_online', true)->count();

            $overview->getCellByColumnAndRow(1, $r)->setValue($vlan->vlan_id);
            $overview->getCellByColumnAndRow(2, $r)->setValue($vlan->vlan_name);
            $overview->getCellByColumnAndRow(3, $r)->setValue($vlan->network_address . '/' . $vlan->cidr_suffix);
            $overview->getCellByColumnAndRow(4, $r)->setValue($vlan->gateway ?? '');
            $overview->getCellByColumnAndRow(5, $r)->setValue($vlan->dhcp_from ?? '');
            $overview->getCellByColumnAndRow(6, $r)->setValue($vlan->dhcp_to ?? '');
            $overview->getCellByColumnAndRow(7, $r)->setValue($online);
            $overview->getCellByColumnAndRow(8, $r)->setValue($ips->count());
            $overview->getCellByColumnAndRow(9, $r)->setValue($vlan->internes_netz ? 'Ja' : 'Nein');
            $overview->getCellByColumnAndRow(10, $r)->setValue($vlan->ipscan ? 'Ja' : 'Nein');
            $overview->getCellByColumnAndRow(11, $r)->setValue($vlan->description ?? '');

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

        // ── Blatt je VLAN ────────────────────────────────────────────────────
        foreach ($vlans as $vlan) {
            $sheetTitle = $this->sanitizeSheetTitle(
                'VLAN ' . str_pad($vlan->vlan_id, 3, '0', STR_PAD_LEFT) . ' ' . $vlan->vlan_name
            );

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetTitle);

            // Info-Block oben
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
                ? ($vlan->dhcp_from . ' – ' . $vlan->dhcp_to)
                : 'Nicht konfiguriert');
            $sheet->setCellValue('A6', 'Beschreibung:');
            $sheet->setCellValue('B6', $vlan->description ?? '');

            $sheet->getStyle('A1:A6')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setWidth(18);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            // IP-Tabelle ab Zeile 8
            $ipHeaders = ['IP-Adresse', 'DNS-Name', 'MAC-Adresse', 'Status', 'Ping (ms)', 'Zuletzt Online', 'DHCP', 'Kommentar'];
            foreach ($ipHeaders as $col => $label) {
                $sheet->getCellByColumnAndRow($col + 1, 8)->setValue($label);
            }

            $lastCol = 'H';
            $this->applyHeaderStyle($sheet, "A8:{$lastCol}8");

            foreach ($vlan->ipAddresses as $row => $ip) {
                $r = $row + 9;
                $sheet->getCellByColumnAndRow(1, $r)->setValue($ip->ip_address);
                $sheet->getCellByColumnAndRow(2, $r)->setValue($ip->dns_name ?? '');
                $sheet->getCellByColumnAndRow(3, $r)->setValue($ip->mac_address ?? '');
                $sheet->getCellByColumnAndRow(4, $r)->setValue($ip->is_online ? 'Online' : 'Offline');
                $sheet->getCellByColumnAndRow(5, $r)->setValue($ip->ping_ms ?? '');
                $sheet->getCellByColumnAndRow(6, $r)->setValue(
                    $ip->last_online_at ? $ip->last_online_at->format('d.m.Y H:i') : ''
                );
                $sheet->getCellByColumnAndRow(7, $r)->setValue($ip->isInDhcpRange() ? 'Ja' : 'Nein');
                $sheet->getCellByColumnAndRow(8, $r)->setValue($ip->comment ?? '');

                // Online grün, Offline grau
                $statusCell = $sheet->getCellByColumnAndRow(4, $r);
                $fill = $ip->is_online ? 'C6EFCE' : 'F2F2F2';
                $font = $ip->is_online ? '276221' : '666666';
                $sheet->getStyleByColumnAndRow(4, $r)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
                    'font' => ['color' => ['rgb' => $font]],
                ]);

                if ($r % 2 === 0) {
                    $this->applyAlternatingRow($sheet, "A{$r}:{$lastCol}{$r}");
                }
            }

            $lastRow = $vlan->ipAddresses->count() + 8;
            if ($lastRow >= 8) {
                $this->applyTableBorder($sheet, "A8:{$lastCol}{$lastRow}");
            }

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet->setAutoFilter("A8:{$lastCol}8");
            $sheet->freezePane('A9');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'netzwerk_export_' . now()->format('Y-m-d_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
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
        // Excel sheet names: max 31 chars, keine Sonderzeichen
        $title = preg_replace('/[\/\\\?\*\[\]:]/', ' ', $title);
        return mb_substr($title, 0, 31);
    }
}
