<?php

namespace App\Modules\Backup\Services;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BackupExportCollector
{
    public function collectTo(string $dir, callable $log = null): array
    {
        $generated = [];
        $actor     = User::role('superadmin')->first();

        // ── Schulen ──────────────────────────────────────────────────────────
        try {
            $schulenDir = "{$dir}/schulen";
            @mkdir($schulenDir, 0755, true);
            app(\App\Modules\Schulen\Services\SchulenExportService::class)
                ->generateAllToDirectory($schulenDir);
            $generated[] = 'Schulen';
            $log && $log('  ✓ Schulen (Matrix, Dienstleistungen, Schulliste)');
        } catch (\Throwable $e) {
            $log && $log('  ⚠ Schulen-Export übersprungen: ' . $e->getMessage());
        }

        // ── Stellenplan ───────────────────────────────────────────────────────
        if ($actor && Schema::hasTable('stellen')) {
            try {
                $d = "{$dir}/stellenplan";
                @mkdir($d, 0755, true);
                $svc = app(\App\Modules\Stellenplan\Services\ExportService::class);
                $this->save($svc->exportXlsx($actor), "{$d}/stellenplan.xlsx");
                $this->save($svc->exportPdf($actor),  "{$d}/stellenplan.pdf");
                $generated[] = 'Stellenplan';
                $log && $log('  ✓ Stellenplan (XLSX + PDF)');
            } catch (\Throwable $e) {
                $log && $log('  ⚠ Stellenplan-Export übersprungen: ' . $e->getMessage());
            }
        }

        // ── Applikationen ─────────────────────────────────────────────────────
        if (Schema::hasTable('applikationen')) {
            try {
                $d = "{$dir}/applikationen";
                @mkdir($d, 0755, true);
                $svc = app(\App\Services\ApplikationExportService::class);
                $this->save($svc->exportXlsx([]), "{$d}/applikationen.xlsx");
                $this->save($svc->exportPdf([]),  "{$d}/applikationen.pdf");
                $generated[] = 'Applikationen';
                $log && $log('  ✓ Applikationen (XLSX + PDF)');
            } catch (\Throwable $e) {
                $log && $log('  ⚠ Applikationen-Export übersprungen: ' . $e->getMessage());
            }
        }

        // ── Aufgaben / Rollen ─────────────────────────────────────────────────
        if ($actor && Schema::hasTable('aufgaben')) {
            try {
                $d = "{$dir}/aufgaben";
                @mkdir($d, 0755, true);
                $svc = app(\App\Services\AufgabenExportService::class);
                $this->save($svc->exportXlsx([], $actor), "{$d}/aufgaben.xlsx");
                $this->save($svc->exportPdf([], $actor),  "{$d}/aufgaben.pdf");
                $generated[] = 'Aufgaben';
                $log && $log('  ✓ Aufgaben/Rollen (XLSX + PDF)');
            } catch (\Throwable $e) {
                $log && $log('  ⚠ Aufgaben-Export übersprungen: ' . $e->getMessage());
            }
        }

        // ── Haushalt (HH) ─────────────────────────────────────────────────────
        if ($actor && Schema::hasTable('hh_budget_years')) {
            try {
                $years = \App\Modules\HH\Models\BudgetYear::orderBy('year', 'desc')->get();
                if ($years->isNotEmpty()) {
                    $d = "{$dir}/haushalt";
                    @mkdir($d, 0755, true);
                    $svc = app(\App\Modules\HH\Services\ExportService::class);
                    foreach ($years as $by) {
                        $this->save($svc->exportExcel($by, $actor), "{$d}/haushalt_{$by->year}.xlsx");
                        $this->save($svc->exportPdf($by, $actor),   "{$d}/haushalt_{$by->year}.pdf");
                    }
                    $count = $years->count();
                    $generated[] = "Haushalt ({$count} " . ($count === 1 ? 'Jahr' : 'Jahre') . ")";
                    $log && $log("  ✓ Haushalt ({$count} Haushaltsjahre)");
                }
            } catch (\Throwable $e) {
                $log && $log('  ⚠ Haushalt-Export übersprungen: ' . $e->getMessage());
            }
        }

        // ── Netzwerk ──────────────────────────────────────────────────────────
        if (\Illuminate\Support\Facades\Schema::hasTable('vlans')) {
            try {
                $d = "{$dir}/netzwerk";
                @mkdir($d, 0755, true);
                app(\App\Modules\Network\Http\Controllers\ExportController::class)
                    ->generateXlsToFile("{$d}/netzwerk.xls", $log);
                $generated[] = 'Netzwerk';
                $log && $log('  ✓ Netzwerk (VLANs + IPs als XLS)');
            } catch (\Throwable $e) {
                $log && $log('  ⚠ Netzwerk-Export übersprungen: ' . $e->getMessage());
            }
        }

        return $generated;
    }

    private function save(Response $response, string $path): void
    {
        if ($response instanceof BinaryFileResponse) {
            $src = $response->getFile()->getPathname();
            copy($src, $path);
            @unlink($src);
        } else {
            file_put_contents($path, $response->getContent());
        }
    }
}
