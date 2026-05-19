<?php

namespace App\Modules\Wid\Services;

use App\Modules\Wid\Models\WidAdvisory;
use App\Modules\Wid\Models\WidSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WidService
{
    private WidSettings $settings;

    public function __construct()
    {
        $this->settings = WidSettings::getInstance();
    }

    public function fetchAdvisories(): Collection
    {
        if (!$this->settings->isConfigured()) return collect();

        return Cache::remember('wid_advisories_raw', 300, function () {
            try {
                $response = Http::withHeaders(['X-Api-Key' => $this->settings->api_key])
                    ->timeout(15)
                    ->get("{$this->settings->api_url}/public/securityAdvisory", [
                        'sort'      => 'published,desc',
                        'size'      => $this->settings->max_items,
                        'page'      => 0,
                        'aboFilter' => 'true',
                    ]);

                if (!$response->successful()) {
                    Log::warning('WID API Fehler: HTTP ' . $response->status());
                    return collect();
                }

                return collect($response->json('content') ?? []);
            } catch (\Exception $e) {
                Log::error('WID API Exception: ' . $e->getMessage());
                return collect();
            }
        });
    }

    public function syncToDatabase(Collection $items): array
    {
        $created = 0;
        $updated = 0;
        $now     = now();

        foreach ($items as $item) {
            $uuid = $item['uuid'] ?? null;
            if (!$uuid) continue;

            $score = isset($item['temporalscore']) ? round($item['temporalscore'] / 10, 1) : null;

            $data = [
                'name'           => $item['name'] ?? '',
                'title'          => $item['title'] ?? null,
                'classification' => $item['classification'] ?? 'keine',
                'temporal_score' => $score,
                'published'      => isset($item['published']) ? \Carbon\Carbon::parse($item['published']) : null,
                'status'         => $item['status'] ?? null,
                'no_patch'       => (bool) ($item['noPatch'] ?? false),
                'exploit'        => (bool) ($item['exploit'] ?? false),
                'fetched_at'     => $now,
            ];

            $existing = WidAdvisory::where('uuid', $uuid)->first();
            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                WidAdvisory::create(array_merge(['uuid' => $uuid], $data));
                $created++;
            }
        }

        Cache::forget('wid_advisories_raw');

        return ['created' => $created, 'updated' => $updated, 'total' => $created + $updated];
    }
}
