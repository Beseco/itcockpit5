<?php

namespace App\Modules\Tickets\Services;

use App\Modules\Tickets\Models\TicketsSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZammadService
{
    private TicketsSettings $settings;

    public function __construct()
    {
        $this->settings = TicketsSettings::getSingleton();
    }

    /**
     * Verbindung testen (GET /api/v1/users/me)
     */
    public function testConnection(): array
    {
        try {
            $response = $this->request('GET', '/api/v1/users/me');

            if ($response === null) {
                return ['success' => false, 'message' => 'Keine Antwort vom Server.'];
            }

            $login = $response['login'] ?? $response['email'] ?? 'unbekannt';

            return [
                'success' => true,
                'message' => "Verbindung erfolgreich. Angemeldet als: {$login}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Tickets eines Benutzers laden (nach E-Mail)
     */
    public function getTicketsForUser(string $email): Collection
    {
        $cacheKey = 'zammad_tickets_' . md5($email);

        return Cache::remember($cacheKey, 180, function () use ($email) {
            try {
                $response = $this->request('GET', '/api/v1/tickets/search', [
                    'query'    => "owner.email:{$email}",
                    'expand'   => 'true',
                    'per_page' => 50,
                    'page'     => 1,
                ]);

                if ($response === null || !isset($response['assets']['Ticket'])) {
                    return collect();
                }

                $tickets = collect($response['assets']['Ticket'])->map(function ($ticket) use ($response) {
                    return [
                        'id'           => $ticket['id'],
                        'number'       => $ticket['number'] ?? '',
                        'title'        => $ticket['title'] ?? '',
                        'state'        => $this->resolveAssetName($response, 'TicketState', $ticket['state_id'] ?? null),
                        'priority'     => $this->resolveAssetName($response, 'TicketPriority', $ticket['priority_id'] ?? null),
                        'group'        => $this->resolveAssetName($response, 'Group', $ticket['group_id'] ?? null),
                        'created_at'   => $ticket['created_at'] ?? null,
                        'updated_at'   => $ticket['updated_at'] ?? null,
                        'pending_time' => $ticket['pending_time'] ?? null,
                    ];
                })->sortByDesc('updated_at')->values();

                return $tickets;
            } catch (\Exception $e) {
                Log::warning('Zammad: Tickets konnten nicht geladen werden', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                return collect();
            }
        });
    }

    /**
     * Ticket-Zähler für Dashboard-Widget
     */
    public function getTicketCount(string $email): array
    {
        $tickets = $this->getTicketsForUser($email);

        $open = 0;
        $pending = 0;

        foreach ($tickets as $ticket) {
            $state = strtolower($ticket['state'] ?? '');
            if (str_contains($state, 'pending') || str_contains($state, 'wartend')) {
                $pending++;
            } elseif (!str_contains($state, 'closed') && !str_contains($state, 'geschlossen') && !str_contains($state, 'merged')) {
                $open++;
            }
        }

        return [
            'total'   => $tickets->count(),
            'open'    => $open,
            'pending' => $pending,
        ];
    }

    /**
     * Cache für einen Benutzer leeren
     */
    public function clearCache(string $email): void
    {
        Cache::forget('zammad_tickets_' . md5($email));
    }

    /**
     * Name aus Assets auflösen (State, Priority, Group)
     */
    private function resolveAssetName(array $response, string $assetType, ?int $id): string
    {
        if (!$id || !isset($response['assets'][$assetType][$id])) {
            return '—';
        }

        return $response['assets'][$assetType][$id]['name'] ?? '—';
    }

    /**
     * HTTP-Request an die Zammad-API senden
     */
    private function request(string $method, string $endpoint, array $query = []): ?array
    {
        $baseUrl = rtrim($this->settings->url, '/');
        $url = $baseUrl . $endpoint;

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Token token=' . $this->settings->api_token,
                    'Accept'        => 'application/json',
                ])
                ->timeout(10)
                ->$method($url, $method === 'GET' ? $query : []);

            if (!$response->successful()) {
                Log::warning('Zammad API Fehler', [
                    'status'   => $response->status(),
                    'endpoint' => $endpoint,
                ]);
                return null;
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Zammad nicht erreichbar', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
