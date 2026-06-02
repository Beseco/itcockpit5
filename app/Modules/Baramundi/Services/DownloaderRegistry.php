<?php

namespace App\Modules\Baramundi\Services;

use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Baramundi\Services\Downloaders\IPackageDownloader;

class DownloaderRegistry
{
    /** @var IPackageDownloader[] */
    private array $downloaders = [];

    public function register(IPackageDownloader $downloader): void
    {
        $this->downloaders[] = $downloader;
    }

    public function findFor(WatchedPackage $pkg): ?IPackageDownloader
    {
        foreach ($this->downloaders as $downloader) {
            if ($downloader->canHandle($pkg)) {
                return $downloader;
            }
        }
        return null;
    }
}
