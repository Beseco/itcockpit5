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
    @endpush
</x-app-layout>
