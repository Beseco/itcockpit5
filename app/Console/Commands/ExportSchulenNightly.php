<?php

namespace App\Console\Commands;

use App\Modules\Schulen\Services\SchulenExportService;
use Illuminate\Console\Command;

class ExportSchulenNightly extends Command
{
    protected $signature   = 'schulen:export-nightly';
    protected $description = 'Erstellt alle 6 Schulen-Exporte (Matrix, Dienstleistungen, Liste) als PDF/Excel/Word';

    public function handle(SchulenExportService $exportService): int
    {
        $date = now()->format('Y-m-d');
        $dir  = storage_path("app/exports/schulen/{$date}");

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->error("Export-Verzeichnis konnte nicht erstellt werden: {$dir}");
            return 1;
        }

        $this->info("Erstelle Schulen-Exporte für {$date}…");

        try {
            $files = $exportService->generateAllToDirectory($dir);

            $totalSize = array_sum(array_map(fn($f) => filesize("{$dir}/{$f}"), $files));
            $this->info(sprintf(
                '  ✓ %d Dateien erstellt, Gesamtgröße: %s',
                count($files),
                $this->formatBytes($totalSize)
            ));
            foreach ($files as $f) {
                $size = filesize("{$dir}/{$f}");
                $this->line("    {$f} (" . $this->formatBytes($size) . ')');
            }
        } catch (\Throwable $e) {
            $this->error('Export fehlgeschlagen: ' . $e->getMessage());
            return 1;
        }

        $this->cleanupOldExports(14);

        return 0;
    }

    private function cleanupOldExports(int $keepDays): void
    {
        $base = storage_path('app/exports/schulen');
        if (!is_dir($base)) return;

        $dirs = glob("{$base}/????-??-??", GLOB_ONLYDIR) ?: [];
        rsort($dirs);

        foreach (array_slice($dirs, $keepDays) as $old) {
            exec('rm -rf ' . escapeshellarg($old));
            $this->line('  Altes Export-Verzeichnis gelöscht: ' . basename($old));
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
        if ($bytes >= 1024)        return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
