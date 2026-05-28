<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Feedback-Auswertung</h2>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('feedback.admin.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    Alle Bewertungen
                </a>
                <a href="{{ route('feedback.admin.comments') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                    Kommentare
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Zeitraum-Filter --}}
            <div class="flex items-center gap-2 flex-wrap">
                @foreach(['today' => 'Heute', '7days' => '7 Tage', '30days' => '30 Tage', '90days' => '90 Tage', 'all' => 'Gesamt'] as $key => $label)
                    <a href="{{ route('feedback.admin.dashboard', ['period' => $key]) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                           {{ $period === $key
                               ? 'bg-indigo-600 text-white border-indigo-600'
                               : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Kacheln --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {{-- Gesamt --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 col-span-2 md:col-span-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Bewertungen</p>
                    <p class="text-4xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                </div>

                {{-- Ø Gesamt --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ø Gesamt</p>
                    <div class="flex items-end gap-2">
                        <p class="text-4xl font-bold {{ $summary['average'] >= 4 ? 'text-green-600' : ($summary['average'] >= 3 ? 'text-yellow-500' : 'text-red-500') }}">
                            {{ $summary['average'] > 0 ? number_format($summary['average'], 1) : '–' }}
                        </p>
                        <span class="text-gray-400 text-sm mb-1">/ 5</span>
                    </div>
                </div>

                {{-- Per-Frage --}}
                @foreach($questionLabels as $key => $label)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <p class="text-xs font-medium text-gray-500 mb-1 leading-tight">{{ $label }}</p>
                        @php $val = $summary['by_question'][$key] ?? 0; @endphp
                        <div class="flex items-end gap-1.5">
                            <p class="text-2xl font-bold {{ $val >= 4 ? 'text-green-600' : ($val >= 3 ? 'text-yellow-500' : 'text-red-500') }}">
                                {{ $val > 0 ? number_format($val, 1) : '–' }}
                            </p>
                            <span class="text-gray-400 text-xs mb-0.5">/ 5</span>
                        </div>
                        {{-- Fortschrittsbalken --}}
                        <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $val >= 4 ? 'bg-green-500' : ($val >= 3 ? 'bg-yellow-400' : 'bg-red-400') }}"
                                 style="width: {{ $val > 0 ? ($val / 5 * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Charts --}}
            {{-- Umfrage-Link --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Umfrage-Link</p>
                <div class="flex items-center gap-2">
                    <input id="feedbackUrl" type="text" readonly
                           value="{{ route('feedback.form') }}"
                           class="flex-1 border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-700 focus:ring-indigo-400 focus:border-indigo-400">
                    <button type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('feedbackUrl').value).then(() => { this.textContent = '✓ Kopiert'; setTimeout(() => this.textContent = 'Kopieren', 2000); })"
                            class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                        Kopieren
                    </button>
                    <a href="{{ route('feedback.form') }}" target="_blank"
                       class="px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                        Öffnen ↗
                    </a>
                </div>
            </div>

            @if($summary['total'] > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Trend-Chart --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Bewertungen im Zeitverlauf</h3>
                    <div class="relative h-56">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                {{-- Fragen-Chart --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Durchschnitt je Frage</h3>
                    <div class="relative h-56">
                        <canvas id="questionChart"></canvas>
                    </div>
                </div>
            </div>
            @else
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                    <p class="text-4xl mb-3">📊</p>
                    <p>Noch keine Bewertungen im gewählten Zeitraum.</p>
                </div>
            @endif

            {{-- Einladung versenden --}}
            <div x-data="feedbackInvite()" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Feedback-Einladung versenden</p>

                @if(session('invite_success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
                        {{ session('invite_success') }}
                    </div>
                @endif

                <form action="{{ route('feedback.admin.invite') }}" method="POST" @submit="submitting = true">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Name / AD-Suche --}}
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-gray-400">(optional / AD-Suche)</span></label>
                            <input
                                type="text"
                                name="recipient_name"
                                x-model="nameQuery"
                                @input.debounce.300ms="search()"
                                @keydown.escape="suggestions = []"
                                @keydown.arrow-down.prevent="focusSuggestion(1)"
                                @keydown.arrow-up.prevent="focusSuggestion(-1)"
                                autocomplete="off"
                                placeholder="Name eingeben oder suchen…"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                            >
                            {{-- Dropdown --}}
                            <div x-show="suggestions.length > 0" x-cloak
                                 @click.away="suggestions = []"
                                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                <template x-for="(user, i) in suggestions" :key="user.email">
                                    <button type="button"
                                            @click="selectUser(user)"
                                            class="w-full text-left px-4 py-2.5 hover:bg-indigo-50 transition-colors text-sm border-b border-gray-50 last:border-0">
                                        <span class="font-medium text-gray-800" x-text="user.name"></span>
                                        <span x-show="user.abteilung" class="ml-1.5 text-xs text-gray-400" x-text="user.abteilung"></span>
                                        <br>
                                        <span class="text-xs text-gray-500" x-text="user.email"></span>
                                    </button>
                                </template>
                                <div x-show="loading" class="px-4 py-2 text-xs text-gray-400">Suche…</div>
                            </div>
                        </div>

                        {{-- E-Mail --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">E-Mail-Adresse <span class="text-red-400">*</span></label>
                            <input
                                type="email"
                                name="recipient_email"
                                x-model="email"
                                required
                                placeholder="empfaenger@beispiel.de"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                            >
                            @error('recipient_email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="submit"
                                :disabled="submitting || !email"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <span x-text="submitting ? 'Sende…' : 'Einladung senden'"></span>
                        </button>
                        <p class="text-xs text-gray-400">Der Empfänger erhält eine E-Mail mit dem direkten Link zur Bewertung.</p>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @if($summary['total'] > 0)
    <script>
        const trendData = @json($trendData);
        const questionLabels = @json(array_values($questionLabels));
        const questionAverages = @json(array_values($summary['by_question']));

        // Trend-Liniendiagramm
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Anzahl',
                        data: trendData.counts,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'yCount',
                    },
                    {
                        label: 'Ø Bewertung',
                        data: trendData.avg_scores,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderDash: [4, 3],
                        tension: 0.3,
                        yAxisID: 'yScore',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { font: { size: 11 } } } },
                scales: {
                    x: { ticks: { font: { size: 10 }, maxTicksLimit: 8 } },
                    yCount: { type: 'linear', position: 'left', ticks: { font: { size: 10 }, precision: 0 }, title: { display: false } },
                    yScore: { type: 'linear', position: 'right', min: 1, max: 5, ticks: { font: { size: 10 } }, grid: { drawOnChartArea: false } },
                }
            }
        });

        // Balkendiagramm Fragen
        new Chart(document.getElementById('questionChart'), {
            type: 'bar',
            data: {
                labels: questionLabels,
                datasets: [{
                    label: 'Ø Bewertung',
                    data: questionAverages,
                    backgroundColor: questionAverages.map(v =>
                        v >= 4 ? 'rgba(16,185,129,0.7)' : v >= 3 ? 'rgba(245,158,11,0.7)' : 'rgba(239,68,68,0.7)'
                    ),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 5, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 }, maxRotation: 30 } }
                }
            }
        });
    </script>
    @endif
    <script>
        function feedbackInvite() {
            return {
                nameQuery: '',
                email: '',
                suggestions: [],
                loading: false,
                submitting: false,
                focused: -1,
                async search() {
                    if (this.nameQuery.length < 2) { this.suggestions = []; return; }
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('feedback.admin.adusers') }}?q=' + encodeURIComponent(this.nameQuery), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.suggestions = await res.json();
                    } catch (e) {
                        this.suggestions = [];
                    }
                    this.loading = false;
                },
                selectUser(user) {
                    this.nameQuery = user.name;
                    this.email     = user.email;
                    this.suggestions = [];
                },
                focusSuggestion(dir) {
                    this.focused = Math.max(0, Math.min(this.suggestions.length - 1, this.focused + dir));
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
