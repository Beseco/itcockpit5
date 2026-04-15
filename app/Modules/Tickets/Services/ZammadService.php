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
     * Tickets laden mit flexiblen Filtern
     *
     * @param string|null $email  Owner-Email (null = alle)
     * @param bool $includeClosed Geschlossene Tickets einbeziehen
     * @param string|null $search Freitext-Suche
     */
    /**
     * @param string|null $email       Owner-Email (null = alle)
     * @param bool        $unassigned  Nur nicht zugewiesene Tickets
     * @param bool        $includeClosed Geschlossene einbeziehen
     * @param string|null $state       Status-Filter (z.B. "open", "pending")
     * @param string|null $search      Freitext-Suche
     */
    public function searchTickets(
        ?string $email = null,
        bool $unassigned = false,
        bool $includeClosed = false,
        ?string $state = null,
        ?string $search = null,
    ): Collection
    {
        $queryParts = [];

        if ($unassigned) {
            $queryParts[] = 'owner_id:1';  // Zammad: owner_id 1 = nicht zugewiesen (System/nobody)
        } elseif ($email) {
            $queryParts[] = 'owner.email:"' . $email . '"';
        }

        if (!$includeClosed && !$state) {
            $queryParts[] = 'NOT state.name:"closed"';
            $queryParts[] = 'NOT state.name:"merged"';
            $queryParts[] = 'NOT state.name:"geschlossen"';
        }

        if ($state) {
            $queryParts[] = 'state.name:"' . $state . '"';
        }

        if ($search) {
            $queryParts[] = $search;
        }

        $query = implode(' AND ', $queryParts) ?: '*';
        $cacheKey = 'zammad_tickets_' . md5($query);

        return Cache::remember($cacheKey, 180, function () use ($query) {
            try {
                $response = $this->request('GET', '/api/v1/tickets/search', [
                    'query'    => $query,
                    'expand'   => 'true',
                    'per_page' => 200,
                    'page'     => 1,
                ]);

                if ($response === null) {
                    return collect();
                }

                $ticketList = $this->extractTickets($response);

                if (empty($ticketList)) {
                    return collect();
                }

                return collect($ticketList)->map(function ($ticket) {
                    return [
                        'id'           => $ticket['id'],
                        'number'       => $ticket['number'] ?? '',
                        'title'        => $ticket['title'] ?? '',
                        'state'        => $ticket['state'] ?? $ticket['state_name'] ?? '—',
                        'priority'     => $ticket['priority'] ?? $ticket['priority_name'] ?? '—',
                        'group'        => $ticket['group'] ?? $ticket['group_name'] ?? '—',
                        'owner'        => $ticket['owner'] ?? $ticket['owner_name'] ?? '—',
                        'created_at'   => $ticket['created_at'] ?? null,
                        'updated_at'   => $ticket['updated_at'] ?? null,
                        'pending_time' => $ticket['pending_time'] ?? null,
                    ];
                })->sortByDesc('updated_at')->values();
            } catch (\Exception $e) {
                Log::warning('Zammad: Tickets konnten nicht geladen werden', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
                return collect();
            }
        });
    }

    /**
     * Ticket-Highlighting-Farbe bestimmen (null | 'yellow' | 'red')
     *
     * Regeln:
     * - new/open: erstellt > 30 Tage UND letzte Änderung > 7 Tage → rot
     * - new/open: erstellt > 14 Tage UND letzte Änderung > 7 Tage → gelb
     * - pending reminder: warten_bis < heute − 7 Tage → rot
     * - pending reminder: warten_bis < heute → gelb
     */
    public static function getTicketColor(array $ticket): ?string
    {
        $state       = strtolower($ticket['state'] ?? '');
        $createdAt   = !empty($ticket['created_at'])   ? \Carbon\Carbon::parse($ticket['created_at'])   : null;
        $updatedAt   = !empty($ticket['updated_at'])   ? \Carbon\Carbon::parse($ticket['updated_at'])   : null;
        $pendingTime = !empty($ticket['pending_time']) ? \Carbon\Carbon::parse($ticket['pending_time']) : null;

        $isOpenState = in_array($state, ['new', 'open', 'neu', 'offen']);
        $isPending   = str_contains($state, 'pending reminder') || str_contains($state, 'wartend');

        if ($isPending && $pendingTime && $pendingTime->isPast()) {
            return $pendingTime->diffInDays(now()) >= 7 ? 'red' : 'yellow';
        }

        if ($isOpenState && $createdAt && $updatedAt) {
            $ageCreated = $createdAt->diffInDays(now());
            $ageUpdated = $updatedAt->diffInDays(now());
            if ($ageCreated >= 30 && $ageUpdated >= 7) return 'red';
            if ($ageCreated >= 14 && $ageUpdated >= 7) return 'yellow';
        }

        return null;
    }

    /**
     * Tickets eines Benutzers laden (Convenience, ohne Closed)
     */
    public function getTicketsForUser(string $email): Collection
    {
        return $this->searchTickets(email: $email, includeClosed: false);
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
            } else {
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
     * Statistik: Tickets pro Mitarbeiter (alle offenen)
     */
    public function getStatsByOwner(Collection $tickets): Collection
    {
        return $tickets->groupBy('owner')->map(function ($group, $owner) {
            $open = 0;
            $pending = 0;
            foreach ($group as $ticket) {
                $state = strtolower($ticket['state'] ?? '');
                if (str_contains($state, 'pending') || str_contains($state, 'wartend')) {
                    $pending++;
                } else {
                    $open++;
                }
            }
            return [
                'owner'   => $owner,
                'total'   => $group->count(),
                'open'    => $open,
                'pending' => $pending,
            ];
        })->sortByDesc('total')->values();
    }

    /**
     * Statistik: Tickets pro Gruppe
     */
    public function getStatsByGroup(Collection $tickets): Collection
    {
        return $tickets->groupBy('group')->map(function ($group, $name) {
            return [
                'group' => $name,
                'total' => $group->count(),
            ];
        })->sortByDesc('total')->values();
    }

    /**
     * Statistik: Tickets pro Priorität
     */
    public function getStatsByPriority(Collection $tickets): Collection
    {
        return $tickets->groupBy('priority')->map(function ($group, $name) {
            return [
                'priority' => $name,
                'total'    => $group->count(),
            ];
        })->sortByDesc('total')->values();
    }

    /**
     * Cache für einen Benutzer leeren
     */
    public function clearCache(string $email): void
    {
        Cache::forget('zammad_tickets_' . md5($email));
    }

    /**
     * Rohe API-Antwort zurückgeben (für Debugging)
     */
    public function debugSearch(string $email): ?array
    {
        return $this->request('GET', '/api/v1/tickets/search', [
            'query'    => 'owner.email:"' . $email . '"',
            'expand'   => 'true',
            'per_page' => 5,
            'page'     => 1,
        ]);
    }

    /**
     * Tickets aus der API-Antwort extrahieren (verschiedene Formate)
     */
    private function extractTickets(array $response): array
    {
        // expand=true: direkt als Array
        if (isset($response[0]['id'])) {
            return $response;
        }

        // assets-Format
        if (isset($response['assets']['Ticket'])) {
            return array_values($response['assets']['Ticket']);
        }

        // Fallback: Prüfe ob numerisch indiziertes Array mit Ticket-Objekten
        $first = reset($response);
        if (is_array($first) && isset($first['id']) && isset($first['title'])) {
            return $response;
        }

        return [];
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
