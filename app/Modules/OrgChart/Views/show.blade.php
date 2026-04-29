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
                'frame_header'=> 'bg-amber-100 text-amber-900',
                'group_bar'   => '#f59e0b',
                'card_border' => 'border-gray-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-amber-50/50',
            ],
            'modern' => [
                'frame_bg'    => 'bg-slate-50',
                'frame_border'=> 'border-slate-300',
                'frame_header'=> 'bg-slate-200 text-slate-900',
                'group_bar'   => '#6366f1',
                'card_border' => 'border-slate-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-slate-50',
            ],
            'behoerde' => [
                'frame_bg'    => 'bg-green-50',
                'frame_border'=> 'border-green-300',
                'frame_header'=> 'bg-green-100 text-green-900',
                'group_bar'   => '#2563eb',
                'card_border' => 'border-green-200',
                'task_bg'     => 'bg-white',
                'subgroup_bg' => 'bg-green-50/50',
            ],
            'bsi' => [
                'frame_bg'    => 'bg-white',
                'frame_border'=> 'border-gray-400',
                'frame_header'=> 'bg-gray-100 text-gray-900',
                'group_bar'   => '#374151',
                'card_border' => 'border-gray-300',
                'task_bg'     => 'bg-gray-50',
                'subgroup_bg' => 'bg-gray-50',
            ],
        ];
        $scheme = $schemes[$version->color_scheme] ?? $schemes['klassisch'];

        // Nodes nach Typ gruppieren
        $topNodes   = $rootNodes->whereIn('type', ['top', 'staff'])->sortBy('sort_order');
        $frameNodes = $rootNodes->where('type', 'frame')->sortBy('sort_order');

        // KPIs
        $totalFte    = (float) $allNodes->sum('headcount');
        $groupCount  = $allNodes->whereIn('type', ['frame', 'group'])->count();
        $taskCount   = $allNodes->where('type', 'task')->count();
        $ifaceCount  = $interfaces->count();
    @endphp

    <style>
        @media print {
            nav, header, .print\:hidden, [class*="print:hidden"] { display: none !important; }
            body { background: white !important; }
            .orgchart-container { padding: 0 !important; }
            @page { size: A3 landscape; margin: 1cm; }
        }
    </style>

    <div class="py-6 px-4 sm:px-6 lg:px-8 orgchart-container">

        @if($version->notes)
        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 print:hidden">
            <strong>Planungsnotizen:</strong> {{ $version->notes }}
        </div>
        @endif

        {{-- KPI-Leiste --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 print:gap-2">
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-gray-800">{{ $groupCount }}</div>
                <div class="text-xs text-gray-500">Gruppen</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-indigo-600">{{ number_format($totalFte, 1, ',', '') }}</div>
                <div class="text-xs text-gray-500">FTE gesamt</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-gray-600">{{ $taskCount }}</div>
                <div class="text-xs text-gray-500">Aufgaben</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-blue-600">{{ $ifaceCount }}</div>
                <div class="text-xs text-gray-500">Schnittstellen</div>
            </div>
        </div>

        {{-- Top-Ebene (Leitung + Stabsstellen) --}}
        @if($topNodes->isNotEmpty())
        <div class="mb-5 flex flex-wrap gap-3 justify-start items-start">
            @foreach($topNodes as $topNode)
            @php
                $topChildren = $allNodes->where('parent_id', $topNode->id)->sortBy('sort_order');
                $topColor    = $topNode->color ?? ($topNode->type === 'top' ? '#7c3aed' : '#059669');
            @endphp
            <div class="rounded-lg border-2 shadow-md overflow-hidden min-w-[180px] max-w-xs"
                 style="border-color: {{ $topColor }};">
                <div class="px-4 py-2 text-white text-sm font-bold text-center"
                     style="background-color: {{ $topColor }};">
                    {{ $topNode->name }}
                    @if($topNode->headcount)
                        <span class="ml-2 text-xs font-normal opacity-80">{{ number_format($topNode->headcount, 1, ',', '') }} FTE</span>
                    @endif
                </div>
                @if($topChildren->isNotEmpty())
                <div class="bg-white px-3 py-2 space-y-0.5">
                    @foreach($topChildren as $tc)
                    <div class="text-xs text-gray-700 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $tc->color ?? $topColor }};"></span>
                        {{ $tc->name }}
                        @if($tc->headcount) <span class="ml-auto text-gray-400">{{ number_format($tc->headcount, 1, ',', '') }}</span> @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- Haupt-Gruppen (Frames) --}}
        @if($frameNodes->isNotEmpty())
        <div class="flex gap-4 overflow-x-auto pb-4 items-start">
            @foreach($frameNodes as $frame)
            @php
                $frameChildren = $allNodes->where('parent_id', $frame->id)
                    ->whereIn('type', ['group'])
                    ->sortBy('sort_order');
                $frameFte = $allNodes->where('parent_id', $frame->id)->sum('headcount')
                    + $allNodes->whereIn('parent_id', $allNodes->where('parent_id', $frame->id)->pluck('id'))->sum('headcount');
                $frameInterfaces = $interfacesByNode->get($frame->id, collect());
            @endphp
            <div class="flex-shrink-0 min-w-[220px] max-w-xs rounded-xl border-2 {{ $scheme['frame_border'] }} {{ $scheme['frame_bg'] }} overflow-hidden shadow-sm">
                {{-- Frame-Header --}}
                <div class="px-4 py-2.5 {{ $scheme['frame_header'] }} font-bold text-sm border-b {{ $scheme['frame_border'] }} flex items-center justify-between">
                    <span>{{ $frame->name }}</span>
                    @if($frameFte > 0)
                    <span class="text-xs font-normal opacity-70">{{ number_format($frameFte, 1, ',', '') }} FTE</span>
                    @endif
                </div>

                {{-- Gruppen innerhalb des Frames --}}
                <div class="p-3 space-y-2">
                    @if($frameChildren->isNotEmpty())
                        @foreach($frameChildren as $group)
                            @include('orgchart::_node_card', [
                                'node'            => $group,
                                'allNodes'        => $allNodes,
                                'interfacesByNode' => $interfacesByNode,
                                'scheme'          => $scheme,
                            ])
                        @endforeach
                    @else
                        <p class="text-xs text-gray-400 text-center py-2">Keine Untergruppen</p>
                    @endif

                    {{-- Frame-eigene Schnittstellen --}}
                    @if($frameInterfaces->isNotEmpty())
                    <div class="pt-2 border-t border-dashed {{ $scheme['frame_border'] }} flex flex-wrap gap-1">
                        @foreach($frameInterfaces as $iface)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            {{ $iface->toNode->name }}: {{ $iface->label }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
            <div class="text-center py-12 text-gray-400">
                <p class="text-sm">Noch keine Gruppen (Rahmen) angelegt.</p>
                @can('orgchart.edit')
                <a href="{{ route('orgchart.edit', $version) }}" class="text-sm text-indigo-600 hover:underline mt-1 inline-block">Zur Bearbeitung →</a>
                @endcan
            </div>
        @endif

        {{-- Schnittstellen-Matrix --}}
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

        {{-- Meta --}}
        <div class="mt-6 text-xs text-gray-400 flex items-center justify-between print:hidden">
            <span>Erstellt von {{ $version->created_by }}</span>
            <span>Zuletzt geändert: {{ $version->updated_at->format('d.m.Y H:i') }}</span>
        </div>

    </div>
</x-app-layout>
