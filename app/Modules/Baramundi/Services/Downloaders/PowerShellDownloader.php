<?php

namespace App\Modules\Baramundi\Services\Downloaders;

use App\Modules\Baramundi\Models\WatchedPackage;

class PowerShellDownloader implements IPackageDownloader
{
    public function canHandle(WatchedPackage $pkg): bool
    {
        return $pkg->download_type === 'powershell';
    }

    public function download(WatchedPackage $pkg, string $version): bool
    {
        $command = $this->substitute($pkg->download_command ?? '', $pkg, $version);

        if (empty($command)) {
            return false;
        }

        $cmd = sprintf(
            'powershell.exe -NonInteractive -NoProfile -ExecutionPolicy Bypass -Command %s 2>&1',
            escapeshellarg($command)
        );

        exec($cmd, $out, $ret);

        return $ret === 0;
    }

    private function substitute(string $template, WatchedPackage $pkg, string $version): string
    {
        return str_replace(
            ['{version}', '{unc_path}', '{package_name}'],
            [$version, $pkg->getUncPath(), $pkg->name],
            $template
        );
    }
}
