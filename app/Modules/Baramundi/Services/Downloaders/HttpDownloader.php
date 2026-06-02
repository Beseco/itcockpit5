<?php

namespace App\Modules\Baramundi\Services\Downloaders;

use App\Modules\Baramundi\Models\WatchedPackage;
use Illuminate\Support\Facades\Http;

class HttpDownloader implements IPackageDownloader
{
    public function canHandle(WatchedPackage $pkg): bool
    {
        return $pkg->download_type === 'http';
    }

    public function download(WatchedPackage $pkg, string $version): bool
    {
        $url = str_replace('{version}', $version, $pkg->download_url ?? '');

        if (empty($url)) {
            return false;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'download');
        $savePath = $pkg->getUncPath() . '\\' . $version . '\\' . $filename;

        $response = Http::timeout(300)->sink($savePath)->get($url);

        return $response->successful();
    }
}
