<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('orgchart.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
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
            <a href="{{ route('orgchart.show', $version) }}"
               class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-md hover:bg-indigo-100 border border-indigo-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Grafische Ansicht
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if(session('success'))
            <div class="rounded-md bg-green-50 border border-green-300 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-md bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- Versions-Metadaten --}}
        <div class="bg-white shadow-sm rounded-lg p-5" x-data="{ metaOpen: false }">
            <button @click="metaOpen = !metaOpen" type="button"
                    class="w-full flex items-center justify-between text-sm font-semibold text-gray-700">
                <span>Versions-Einstellungen</span>
                <svg class="w-4 h-4 transition-transform" :class="metaOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="metaOpen" x-cloak class="mt-4">
                <form action="{{ route('orgchart.update', $version) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2">
                        <x-input-label for="meta_name" value="Name *" />
                        <x-text-input id="meta_name" name="name" type="text" class="mt-1 block w-full"
                                      value="{{ old('name', $version->name) }}" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="meta_desc" value="Beschreibung" />
                        <textarea id="meta_desc" name="description" rows="2"
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $version->description) }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="meta_status" value="Status" />
                        <select id="meta_status" name="status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach(\App\Modules\OrgChart\Models\OrgVersion::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $version->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-amber-600">Aktivierung archiviert automatisch die bisherige aktive Version.</p>
                    </div>
                    <div>
                        <x-input-label for="meta_scheme" value="Farbschema" />
                        <select id="meta_scheme" name="color_scheme"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            @foreach(\App\Modules\OrgChart\Models\OrgVersion::COLOR_SCHEMES as $val => $label)
                                <option value="{{ $val }}" @selected(old('color_scheme', $version->color_scheme) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="meta_notes" value="Planungsnotizen" />
                        <textarea id="meta_notes" name="notes" rows="3"
                                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                  placeholder="Entscheidungshistorie, offene Fragen…">{{ old('notes', $version->notes) }}</textarea>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700 uppercase tracking-widest">
                            Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ── Linke Spalte: Baum-Editor ── --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Struktur bearbeiten</h3>
                    </div>

                    {{-- Knoten hinzufügen --}}
                    <div x-data="{ addOpen: false }" class="mb-5">
                        <button @click="addOpen = !addOpen" type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md hover:bg-indigo-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Knoten hinzufügen
                        </button>
                        <div x-show="addOpen" x-cloak class="mt-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <form action="{{ route('orgchart.nodes.store', $version) }}" method="POST"
                                  class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @csrf
                                <div class="col-span-2 sm:col-span-3">
                                    <x-input-label value="Übergeordneter Knoten" />
                                    <select name="parent_id"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">— Oberste Ebene —</option>
                                        @foreach($version->nodes->sortBy('name') as $n)
                                            <option value="{{ $n->id }}">
                                                {{ str_repeat('· ', $n->parent_id ? 1 : 0) }}[{{ \App\Modules\OrgChart\Models\OrgNode::TYPE_LABELS[$n->type] ?? $n->type }}] {{ $n->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Typ *" />
                                    <select name="type"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        @foreach(\App\Modules\OrgChart\Models\OrgNode::TYPE_LABELS as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <x-input-label value="Name *" />
                                    <x-text-input name="name" type="text" class="mt-1 block w-full" placeholder="Name des Knotens" required />
                                </div>
                                <div>
                                    <x-input-label value="Kapazität (FTE)" />
                                    <x-text-input name="headcount" type="number" step="0.5" min="0" class="mt-1 block w-full" placeholder="z. B. 2.5" />
                                </div>
                                <div>
                                    <x-input-label value="Farbe (HEX)" />
                                    <div class="mt-1 flex gap-2 items-center">
                                        <input type="color" name="color" value="#6366f1"
                                               class="h-9 w-12 rounded border border-gray-300 cursor-pointer p-0.5">
                                        <x-text-input name="color_text" type="text" class="block w-full" placeholder="#6366f1" />
                                    </div>
                                </div>
                                <div class="col-span-2 sm:col-span-3">
                                    <x-input-label value="Beschreibung / Aufgaben" />
                                    <textarea name="description" rows="2"
                                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                              placeholder="Kurze Beschreibung der Aufgaben…"></textarea>
                                </div>
                                <div class="col-span-2 sm:col-span-3 flex justify-end gap-2">
                                    <button type="button" @click="addOpen = false"
                                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                                    <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-indigo-600 text-white hover:bg-indigo-700 rounded-md font-semibold">Hinzufügen</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Baum-Ansicht --}}
                    @if($rootNodes->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">Noch keine Knoten. Fügen Sie den ersten Knoten hinzu.</p>
                    @else
                        @include('orgchart::_tree_nodes', ['nodes' => $rootNodes, 'allNodes' => $version->nodes, 'version' => $version, 'depth' => 0])
                    @endif
                </div>
            </div>

            {{-- ── Rechte Spalte: Schnittstellen ── --}}
            <div class="space-y-4">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Schnittstellen</h3>

                    @if($interfaces->isEmpty())
                        <p class="text-xs text-gray-400 mb-4">Noch keine Schnittstellen definiert.</p>
                    @else
                    <div class="space-y-2 mb-4">
                        @foreach($interfaces as $iface)
                        <div class="flex items-start justify-between gap-2 p-2 bg-gray-50 rounded border border-gray-200 text-xs">
                            <div>
                                <div class="font-medium text-gray-800">{{ $iface->fromNode->name }}</div>
                                <div class="text-gray-400">↔ {{ $iface->toNode->name }}</div>
                                <div class="text-indigo-700 mt-0.5">{{ $iface->label }}</div>
                                @if($iface->description)
                                    <div class="text-gray-500 mt-0.5">{{ $iface->description }}</div>
                                @endif
                            </div>
                            <form action="{{ route('orgchart.interfaces.destroy', [$version, $iface]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600" title="Löschen">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <form action="{{ route('orgchart.interfaces.store', $version) }}" method="POST" class="space-y-3 border-t border-gray-100 pt-4">
                        @csrf
                        <div>
                            <x-input-label value="Von Gruppe" />
                            <select name="from_node_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">— wählen —</option>
                                @foreach($allNodes as $n)
                                    <option value="{{ $n->id }}">{{ $n->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Zu Gruppe" />
                            <select name="to_node_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">— wählen —</option>
                                @foreach($allNodes as $n)
                                    <option value="{{ $n->id }}">{{ $n->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Thema / Label *" />
                            <x-text-input name="label" type="text" class="mt-1 block w-full"
                                          placeholder="z. B. Zugriffsverwaltung" required />
                        </div>
                        <div>
                            <x-input-label value="Beschreibung" />
                            <textarea name="description" rows="2"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                      placeholder="Optional: Details zur Schnittstelle…"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full px-3 py-2 text-xs bg-indigo-600 text-white hover:bg-indigo-700 rounded-md font-semibold">
                            Schnittstelle anlegen
                        </button>
                    </form>
                </div>

                {{-- Hinweis --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-xs text-amber-800">
                    <strong>Tipp:</strong> Legen Sie zuerst alle Gruppen an, bevor Sie Schnittstellen definieren. Schnittstellen werden in der grafischen Ansicht als farbige Tags unterhalb der jeweiligen Gruppe angezeigt.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
