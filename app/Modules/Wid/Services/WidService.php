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
            return $this->doFetch();
        });
    }

    public function doFetch(): Collection
    {
        try {
            $params = [
                'sort' => 'published,desc',
                'size' => $this->settings->max_items,
                'page' => 0,
            ];

            if ($this->settings->abo_filter) {
                $params['aboFilter'] = 'true';
            }

            $response = $this->http()
                ->get("{$this->settings->api_url}/public/securityAdvisory", $params);

            if (!$response->successful()) {
                Log::warning('WID API Fehler: HTTP ' . $response->status() . ' – ' . $response->body());
                return collect();
            }

            $body = $response->json();

            if (isset($body['content'])) {
                return collect($body['content']);
            }
            if (is_array($body) && array_is_list($body)) {
                return collect($body);
            }

            Log::warning('WID API: Unerwartete Antwortstruktur', ['keys' => array_keys($body ?? [])]);
            return collect();
        } catch (\Exception $e) {
            Log::error('WID API Exception: ' . $e->getMessage());
            return collect();
        }
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

    /**
     * Lädt die Beschreibung für alle Einträge nach, die noch keine haben.
     * Gibt die Anzahl der nachgeladenen Descriptions zurück.
     */
    public function fetchMissingDetails(): int
    {
        $missing = WidAdvisory::where('detail_fetched', false)->get();
        $count   = 0;

        foreach ($missing as $advisory) {
            try {
                $response = $this->http()
                    ->get("{$this->settings->api_url}/public/securityAdvisory/{$advisory->uuid}");

                if (!$response->successful()) {
                    $advisory->update(['detail_fetched' => true]); // nicht nochmal versuchen
                    continue;
                }

                $detail = $response->json();

                $description = $detail['description'] ?? null;

                // Original-Datum: verschiedene mögliche Feldnamen probieren
                $originalDate = null;
                foreach (['initialPublished', 'publishedDate', 'releaseDate', 'firstPublished', 'initialRelease', 'datum', 'originalPublished', 'created'] as $field) {
                    if (!empty($detail[$field])) {
                        try {
                            $originalDate = \Carbon\Carbon::parse($detail[$field]);
                        } catch (\Exception) {}
                        break;
                    }
                }

                $advisory->update([
                    'description'        => $description,
                    'published_original' => $originalDate,
                    'detail_fetched'     => true,
                ]);

                $count++;
            } catch (\Exception $e) {
                Log::warning("WID Detail-Fehler für {$advisory->name}: " . $e->getMessage());
            }
        }

        return $count;
    }

    private function http()
    {
        return Http::withHeaders(['X-Api-Key' => $this->settings->api_key])
            ->timeout(15)
            ->withoutVerifying();
    }
}
