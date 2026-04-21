<?php

namespace App\Modules\Server\Http\Controllers;

use App\Modules\Server\Models\CheckMkSettings;
use App\Modules\Server\Models\Server;
use App\Services\CheckMkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CheckMkController extends Controller
{
    /** AJAX: Monitoring-Daten für einen Server */
    public function hostData(Server $server, CheckMkService $checkMk): JsonResponse
    {
        if (! $checkMk->isConfigured()) {
            return response()->json(['error' => 'CheckMK ist nicht konfiguriert oder deaktiviert.'], 503);
        }

        $hostname = $server->checkmk_alias ?: $server->dns_hostname ?: $server->name;
        return response()->json($checkMk->getHostData($hostname));
    }

    /** AJAX: Verbindungstest mit den aktuell gespeicherten oder eingegebenen Werten */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'url'        => ['required', 'string'],
            'site'       => ['required', 'string'],
            'username'   => ['required', 'string'],
            'secret'     => ['nullable', 'string'],
            'verify_ssl' => ['nullable', 'boolean'],
        ]);

        $saved = CheckMkSettings::getSingleton();

        $settings          = new CheckMkSettings();
        $settings->url        = $request->url;
        $settings->site       = $request->site;
        $settings->username   = $request->username;
        $settings->secret     = filled($request->secret) ? $request->secret : $saved->secret;
        $settings->verify_ssl = $request->boolean('verify_ssl');
        $settings->enabled    = true;

        try {
            $http = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$settings->username} {$settings->secret}",
                'Accept'        => 'application/json',
            ])->timeout(8);

            if (! $settings->verify_ssl) {
                $http = $http->withoutVerifying();
            }

            $base     = rtrim($settings->url, '/') . '/' . trim($settings->site, '/') . '/check_mk/api/1.0';
            $response = $http->get($base . '/version');

            if ($response->successful()) {
                $version = $response->json('versions.checkmk') ?? $response->json('version') ?? '?';
                return response()->json(['ok' => true, 'message' => "Verbindung erfolgreich – CheckMK Version: {$version}"]);
            }

            return response()->json(['ok' => false, 'message' => "HTTP {$response->status()}: " . ($response->json('title') ?? $response->body())]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Einstellungen speichern */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled'    => ['nullable', 'boolean'],
            'url'        => ['required', 'string', 'max:500'],
            'site'       => ['required', 'string', 'max:100'],
            'username'   => ['required', 'string', 'max:100'],
            'secret'     => ['nullable', 'string', 'max:500'],
            'verify_ssl' => ['nullable', 'boolean'],
        ]);

        $settings = CheckMkSettings::getSingleton();
        $settings->fill([
            'enabled'    => $request->boolean('enabled'),
            'url'        => $request->url,
            'site'       => $request->site,
            'username'   => $request->username,
            'verify_ssl' => $request->boolean('verify_ssl'),
        ]);

        if (filled($request->secret)) {
            $settings->secret = $request->secret;
        }

        $settings->save();

        return redirect()->route('server.settings')
            ->with('success', 'CheckMK-Einstellungen gespeichert.');
    }
}
