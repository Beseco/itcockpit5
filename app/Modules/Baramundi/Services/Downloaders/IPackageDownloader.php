<?php

namespace App\Modules\Baramundi\Services\Downloaders;

use App\Modules\Baramundi\Models\WatchedPackage;

interface IPackageDownloader
{
    public function canHandle(WatchedPackage $pkg): bool;

    /**
     * Executes the download for the given version.
     * Returns true on success, false on failure.
     * May throw on unrecoverable errors.
     */
    public function download(WatchedPackage $pkg, string $version): bool;
}
