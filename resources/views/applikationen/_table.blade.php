<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                @php $nextOrder = $order === 'ASC' ? 'DESC' : 'ASC'; @endphp
                <tr>
                    @foreach ([
                        'name'             => 'Name / Hersteller',
                        'baustein'         => 'Baustein',
                        'sg'               => 'Sachgebiet',
                        'verantwortlich_sg'=> 'Verantwortlichkeiten',
                    ] as $col => $label)
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <a href="{{ route('applikationen.index', array_merge(request()->query(), ['sort' => $col, 'order' => $sort === $col ? $nextOrder : 'ASC', 'filter_applied' => '1'])) }}"
                           class="hover:text-gray-800 flex items-center gap-1 app-table-link">
                            {{ $label }}
                            @if($sort === $col) <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span> @endif
                        </a>
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schutzbedarf</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($apps as $app)
                    @php $revisionFaellig = $app->revision_date && $app->revision_date->isPast(); @endphp
                    <tr class="hover:bg-gray-50 {{ $revisionFaellig ? 'border-l-2 border-l-red-400' : '' }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('applikationen.show', $app) }}" class="font-medium text-indigo-700 hover:underline">{{ $app->name }}</a>
                            @if ($app->hersteller)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $app->hersteller }}</div>
                            @endif
                            @if ($app->einsatzzweck)
                                <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($app->einsatzzweck, 60) }}</div>
                            @endif
                            @if ($revisionFaellig)
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Revision fällig seit {{ $app->revision_date->format('d.m.Y') }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($app->baustein)
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">{{ $app->baustein }}</span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($app->abteilung)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In Abteilungsdatenbank gefunden"></span>
                                    {{ $app->abteilung->anzeigename }}
                                </div>
                            @elseif ($app->sg)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="Nicht in Abteilungsdatenbank zugeordnet"></span>
                                    {{ $app->sg }}
                                </div>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($app->verantwortlichAdUser)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In AD-Datenbank gefunden"></span>
                                    {{ $app->verantwortlichAdUser->anzeigenameOrName }}
                                </div>
                            @elseif ($app->verantwortlich_sg)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                    {{ $app->verantwortlich_sg }}
                                </div>
                            @elseif ($app->verantwortlich_ad_user_id)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                    <span class="text-gray-400 italic text-xs">Verantwortl. nicht gefunden</span>
                                </div>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                            @if ($app->adminUser)
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500"></span>
                                    <span class="text-xs text-gray-500">Admin: {{ $app->adminUser->name }}</span>
                                </div>
                            @elseif ($app->admin)
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                    <span class="text-xs text-gray-500">Admin: {{ $app->admin }}</span>
                                </div>
                            @elseif ($app->admin_user_id)
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                    <span class="text-xs text-gray-400 italic">Admin nicht gefunden</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $sbDot = ['A'=>'bg-green-500','B'=>'bg-yellow-400','C'=>'bg-red-500'];
                                $sbTxt = ['A'=>'text-green-700','B'=>'text-yellow-700','C'=>'text-red-700'];
                            @endphp
                            <div class="space-y-0.5">
                                @foreach(['confidentiality'=>'Vertraulichkeit','integrity'=>'Integrität','availability'=>'Verfügbarkeit'] as $field => $lbl)
                                    @php $val = $app->$field; @endphp
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $sbDot[$val] ?? 'bg-gray-400' }}"></span>
                                        <span class="text-gray-400 w-24">{{ $lbl }}</span>
                                        <span class="font-semibold {{ $sbTxt[$val] ?? 'text-gray-600' }}">{{ $val }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1 items-center">
                                <a href="{{ route('applikationen.show', $app) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded transition-colors"
                                   title="Detail">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @can('applikationen.edit')
                                <a href="{{ route('applikationen.edit', $app) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50 rounded transition-colors"
                                   title="Bearbeiten">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Keine Applikationen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between flex-wrap gap-2 app-table-pagination">
    <x-per-page-select :per-page="$perPage" />
    @if ($apps->hasPages())
        <div>{{ $apps->links() }}</div>
    @endif
</div>
