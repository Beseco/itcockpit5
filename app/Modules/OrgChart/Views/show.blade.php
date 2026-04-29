<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('orgchart.index') }}" class="text-gray-400 hover:text-gray-600 print:hidden">← Zurück</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $version->name }}</h2>
                @php
                    $sc = match($version->status) {
                        'aktiv'      => 'bg-green-100 text-green-800',
                        'abstimmung' => 'bg-yellow-100 text-yellow-800',
                        'entwurf'    => 'bg-gray-100 text-gray-600',
                        'archiviert' => 'bg-slate-100 text-slate-600',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="px-2 py-0.5 text-xs rounded {{ $sc }}">
                    {{ \App\Modules\OrgChart\Models\OrgVersion::STATUS_LABELS[$version->status] }}
                </span>
            </div>
            <div class="flex items-center gap-2 print:hidden">
                @can('orgchart.edit')
                <a href="{{ route('orgchart.edit', $version) }}"
                   class="inline-flex items-center px-3 py-2 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-md hover:bg-yellow-100 border border-yellow-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Bearbeiten
                </a>
                @endcan
                <a href="{{ route('orgchart.export-pdf', $version) }}"
                   class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 text-xs font-medium rounded-md hover:bg-red-100 border border-red-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>
                <button onclick="window.print()"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 border border-gray-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Drucken
                </button>
            </div>
        </div>
    </x-slot>

    @php
        // Farbschemata
        $schemes = [
            'klassisch' => [
                'frame_bg'    => 'bg-amber-50',
                'frame_border'=> 'border-amber-300',
                'frame_header'=> '#92400e',
                'frame_hbg'   => '#fef3c7',
                'group_bar'   => '#f59e0b',
                'card_border' => 'border-gray-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-amber-50',
                'leitung_bg'  => '#f59e0b',
            ],
            'modern' => [
                'frame_bg'    => 'bg-slate-50',
                'frame_border'=> 'border-slate-300',
                'frame_header'=> '#1e293b',
                'frame_hbg'   => '#e2e8f0',
                'group_bar'   => '#6366f1',
                'card_border' => 'border-slate-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-slate-50',
                'leitung_bg'  => '#6366f1',
            ],
            'behoerde' => [
                'frame_bg'    => 'bg-green-50',
                'frame_border'=> 'border-green-300',
                'frame_header'=> '#14532d',
                'frame_hbg'   => '#dcfce7',
                'group_bar'   => '#2563eb',
                'card_border' => 'border-green-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-green-50',
                'leitung_bg'  => '#2563eb',
            ],
            'bsi' => [
                'frame_bg'    => 'bg-white',
                'frame_border'=> 'border-gray-400',
                'frame_header'=> '#111827',
                'frame_hbg'   => '#f3f4f6',
                'group_bar'   => '#374151',
                'card_border' => 'border-gray-300',
                'task_bg'     => 'bg-gray-50',
                'subgroup_bg' => 'bg-gray-50',
                'leitung_bg'  => '#374151',
            ],
        ];
        $scheme = $schemes[$version->color_scheme] ?? $schemes['klassisch'];

        // Top-/Stabsstellen-Knoten (oberste Ebene)
        $topNodes   = $rootNodes->whereIn('type', ['top', 'staff'])->sortBy('sort_order');
        $frameNodes = $rootNodes->where('type', 'frame')->sortBy('sort_order');

        // KPIs
        $totalFte   = (float) $allNodes->sum('headcount');
        $groupCount = $allNodes->whereIn('type', ['frame', 'group'])->count();
        $taskCount  = $allNodes->where('type', 'task')->count();
        $ifaceCount = $interfaces->count();
    @endphp

    <style>
        @media print {
            nav, header, .print\:hidden, [class*="print:hidden"] { display: none !important; }
            body, html { background: white !important; }
            .orgchart-wrap { padding: 0 !important; }
            @page { size: A3 landscape; margin: 1cm; }
            .overflow-x-auto { overflow: visible !important; }
        }
    </style>

    <div class="py-4 px-4 sm:px-6 lg:px-8 orgchart-wrap">

        @if($version->notes)
        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 text-sm text-amber-800 print:hidden">
            <strong>Planungsnotizen:</strong> {{ $version->notes }}
        </div>
        @endif

        {{-- KPI-Leiste --}}
        <div class="grid grid-cols-4 gap-3 mb-5 print:hidden">
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2.5 text-center shadow-sm">
                <div class="text-xl font-bold text-gray-800">{{ $groupCount }}</div>
                <div class="text-xs text-gray-500">Gruppen / Themenblöcke</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2.5 text-center shadow-sm">
                <div class="text-xl font-bold text-indigo-600">{{ number_format($totalFte, 1, ',', '') }}</div>
                <div class="text-xs text-gray-500">FTE gesamt</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2.5 text-center shadow-sm">
                <div class="text-xl font-bold text-gray-600">{{ $taskCount }}</div>
                <div class="text-xs text-gray-500">Aufgaben</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2.5 text-center shadow-sm">
                <div class="text-xl font-bold text-blue-600">{{ $ifaceCount }}</div>
                <div class="text-xs text-gray-500">Schnittstellen</div>
            </div>
        </div>

        {{-- ── Leitung + Stabsstellen (oberste Ebene) ─────────────────────────── --}}
        @if($topNodes->isNotEmpty())
        <div class="flex flex-wrap gap-4 justify-center mb-6 items-start">
            @foreach($topNodes as $topNode)
            @php
                $topColor    = $topNode->color ?? ($topNode->type === 'top' ? '#7c3aed' : '#059669');
                $topChildren = $allNodes->where('parent_id', $topNode->id)->sortBy('sort_order');
            @endphp
            <div class="rounded-xl border-2 shadow overflow-hidden"
                 style="border-color: {{ $topColor }}; min-width: 180px; max-width: 260px;">
                <div class="px-4 py-2 text-white text-sm font-bold text-center leading-tight"
                     style="background-color: {{ $topColor }};">
                    {{ $topNode->name }}
                    @if($topNode->headcount)
                        <div class="text-xs font-normal opacity-75 mt-0.5">{{ number_format($topNode->headcount, 1, ',', '') }} FTE</div>
                    @endif
                </div>
                @if($topChildren->isNotEmpty())
                <div class="bg-white divide-y divide-gray-100">
                    @foreach($topChildren as $tc)
                    <div class="px-3 py-1.5 text-xs text-gray-700 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $tc->color ?? $topColor }};"></span>
                        <span>{{ $tc->name }}</span>
                        @if($tc->headcount) <span class="ml-auto text-gray-400 font-mono">{{ number_format($tc->headcount, 1, ',', '') }}</span> @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- ── Haupt-Gruppen (Frames) ───────────────────────────────────────────── --}}
        @if($frameNodes->isNotEmpty())
        <div class="overflow-x-auto pb-4">
            <div class="flex gap-4 items-start" style="min-width: max-content;">
                @foreach($frameNodes as $frame)
                @php
                    // Leitung innerhalb des Frames (type=top direkt unter frame)
                    $frameLeitungen = $allNodes->where('parent_id', $frame->id)->where('type', 'top')->sortBy('sort_order');
                    // Themenblöcke (type=group) direkt unter frame
                    $frameGroups    = $allNodes->where('parent_id', $frame->id)->where('type', 'group')->sortBy('sort_order');
                    // Aufgaben direkt unter frame (ohne Themenblock)
                    $frameTasks     = $allNodes->where('parent_id', $frame->id)->where('type', 'task')->sortBy('sort_order');
                    // Schnittstellen des Frames
                    $frameIfaces    = $interfacesByNode->get($frame->id, collect());
                    // FTE-Summe des Frames
                    $frameFte = 0;
                    foreach ($allNodes->where('version_id', $frame->version_id) as $fn) {
                        // nur direkte Nachkommen zählen
                    }
                    $frameFte = $allNodes->whereIn('parent_id', array_merge(
                        [$frame->id],
                        $allNodes->where('parent_id', $frame->id)->pluck('id')->toArray()
                    ))->sum('headcount');
                @endphp

                <div class="rounded-xl border-2 {{ $scheme['frame_border'] }} {{ $scheme['frame_bg'] }} overflow-hidden shadow-sm flex-shrink-0"
                     style="min-width: 220px;">

                    {{-- Frame-Header --}}
                    <div class="px-4 py-2 font-bold text-sm border-b {{ $scheme['frame_border'] }} flex items-center justify-between"
                         style="background-color: {{ $scheme['frame_hbg'] }}; color: {{ $scheme['frame_header'] }};">
                        <span>{{ $frame->name }}</span>
                        @if($frameFte > 0)
                        <span class="text-xs font-normal opacity-60 ml-2">{{ number_format($frameFte, 1, ',', '') }} FTE</span>
                        @endif
                    </div>

                    {{-- Leitung innerhalb der Gruppe --}}
                    @if($frameLeitungen->isNotEmpty())
                    <div class="flex justify-center gap-3 px-3 pt-3 pb-1">
                        @foreach($frameLeitungen as $fl)
                        @php $lColor = $fl->color ?? $scheme['leitung_bg']; @endphp
                        <div class="rounded-lg px-3 py-1.5 text-center shadow-sm text-xs font-semibold text-white"
                             style="background-color: {{ $lColor }}; min-width: 120px;">
                            {{ $fl->name }}
                            @if($fl->headcount) <div class="text-[10px] font-normal opacity-75">{{ number_format($fl->headcount, 1, ',', '') }} FTE</div> @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Themenblöcke nebeneinander --}}
                    @if($frameGroups->isNotEmpty())
                    <div class="flex gap-2 p-3 items-start">
                        @foreach($frameGroups as $group)
                            @include('orgchart::_node_card', [
                                'node'             => $group,
                                'allNodes'         => $allNodes,
                                'interfacesByNode' => $interfacesByNode,
                                'scheme'           => $scheme,
                            ])
                        @endforeach
                    </div>
                    @endif

                    {{-- Aufgaben direkt unter Frame (ohne Themenblock) --}}
                    @if($frameTasks->isNotEmpty())
                    <div class="px-3 pb-3 {{ $frameGroups->isNotEmpty() ? 'border-t border-dashed ' . $scheme['frame_border'] . ' pt-3' : '' }}">
                        <div class="text-[10px] text-gray-400 uppercase tracking-wider mb-1.5 font-medium">Aufgaben</div>
                        <div class="space-y-1">
                            @foreach($frameTasks as $task)
                            @php $taskColor = $task->color ?? null; @endphp
                            <div class="flex items-start gap-1.5 text-xs {{ $taskColor ? 'rounded px-1.5 py-0.5' : '' }}"
                                 @if($taskColor) style="background-color: {{ $taskColor }}18;" @endif>
                                <span class="mt-1 w-1.5 h-1.5 rounded-full shrink-0"
                                      style="background-color: {{ $taskColor ?? $scheme['group_bar'] }};"></span>
                                <span class="text-gray-800">{{ $task->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Frame-eigene Schnittstellen --}}
                    @if($frameIfaces->isNotEmpty())
                    <div class="px-3 pb-2 border-t border-blue-100 bg-blue-50/40 flex flex-wrap gap-1 pt-2">
                        @foreach($frameIfaces as $iface)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-white text-blue-700 border border-blue-200 shadow-sm">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            {{ $iface->toNode->name }}: {{ $iface->label }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                </div>
                @endforeach
            </div>
        </div>
        @else
            <div class="text-center py-12 text-gray-400">
                <p class="text-sm">Noch keine Gruppen (Rahmen) angelegt.</p>
                @can('orgchart.edit')
                <a href="{{ route('orgchart.edit', $version) }}" class="text-sm text-indigo-600 hover:underline mt-1 inline-block">Zur Bearbeitung →</a>
                @endcan
            </div>
        @endif

        {{-- ── Schnittstellen-Matrix ────────────────────────────────────────────── --}}
        @if($interfaces->isNotEmpty())
        <div class="mt-8 print:mt-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Schnittstellen-Matrix</h3>
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-500 font-medium">Von</th>
                            <th class="px-4 py-2 text-left text-gray-500 font-medium">Zu</th>
                            <th class="px-4 py-2 text-left text-gray-500 font-medium">Thema</th>
                            <th class="px-4 py-2 text-left text-gray-500 font-medium">Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($interfaces as $iface)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-800">{{ $iface->fromNode->name }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $iface->toNode->name }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $iface->label }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $iface->description ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="mt-6 text-xs text-gray-400 flex items-center justify-between print:hidden">
            <span>Erstellt von {{ $version->created_by }}</span>
            <span>Zuletzt geändert: {{ $version->updated_at->format('d.m.Y H:i') }}</span>
        </div>
    </div>
</x-app-layout>
