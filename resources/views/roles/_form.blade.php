@php
    $isSuperAdmin = isset($role) && $role->name === 'Superadministrator';
    $actionLabels = [
        'view'           => 'Anzeigen',
        'view_sensitive' => 'Sensible Daten',
        'create'         => 'Erstellen',
        'edit'           => 'Bearbeiten',
        'delete'         => 'Löschen',
        'manage'         => 'Verwalten',
    ];
    // Collect which columns actually exist across all modules
    $existingActions = collect($permissionsByModule)->flatMap(fn($actions) => $actions->keys())->unique()->values();
@endphp

{{-- Rollenname --}}
<div class="mb-6">
    <x-input-label for="name" value="Rollenname" />
    @if ($isSuperAdmin)
        <x-text-input id="name" class="block mt-1 w-full bg-gray-100 cursor-not-allowed"
                      type="text" name="name" value="{{ $role->name }}" readonly />
        <p class="mt-1 text-xs text-gray-500">Der Superadministrator-Rollenname kann nicht geändert werden.</p>
    @else
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                      :value="old('name', isset($role) ? $role->name : '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    @endif
</div>

{{-- Berechtigungen --}}
<div class="mb-6">
    <x-input-label value="Berechtigungen" />
    <p class="mt-1 mb-3 text-sm text-gray-500">Wählen Sie die Berechtigungen für diese Rolle aus.</p>

    @if ($isSuperAdmin)
        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-md text-sm text-indigo-800">
            Der Superadministrator hat automatisch Zugriff auf alle Berechtigungen im System. Eine manuelle Zuweisung ist nicht erforderlich.
        </div>
    @else
        <div class="border border-gray-200 rounded-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200" x-data="rolesForm()">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">Modul</th>
                        @foreach ($existingActions as $action)
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                {{ $actionLabels[$action] ?? $action }}
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Alle</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($permissionsByModule as $module => $actions)
                        @php
                            $modulePermNames = $actions->map(fn($p) => $p->name)->values()->toArray();
                        @endphp
                        <tr class="hover:bg-gray-50" x-data="{
                            modulePerms: {{ \Illuminate\Support\Js::from($modulePermNames) }},
                            get allChecked() {
                                return this.modulePerms.every(n => this.isChecked(n));
                            },
                            isChecked(name) {
                                return checkedPerms.has(name);
                            },
                            toggleAll() {
                                if (this.allChecked) {
                                    this.modulePerms.forEach(n => checkedPerms.delete(n));
                                } else {
                                    this.modulePerms.forEach(n => checkedPerms.add(n));
                                }
                                checkedPerms = new Set(checkedPerms);
                            },
                            toggle(name) {
                                if (checkedPerms.has(name)) {
                                    checkedPerms.delete(name);
                                } else {
                                    checkedPerms.add(name);
                                }
                                checkedPerms = new Set(checkedPerms);
                            }
                        }">
                            <td class="px-4 py-3 text-sm font-medium text-gray-700">
                                @php
                                    $mod = $moduleDisplayNames[$module] ?? null;
                                    $displayName = $mod?->display_name ?? $module;
                                    $description = $mod?->description ?? null;
                                @endphp
                                <div class="flex items-center gap-1">
                                    <span>{{ $displayName }}</span>
                                    @if ($description)
                                        <span class="relative group cursor-default">
                                            <svg class="w-3.5 h-3.5 text-gray-400 hover:text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="absolute left-5 top-0 z-10 hidden group-hover:block w-56 bg-gray-800 text-white text-xs rounded shadow-lg px-3 py-2 leading-snug">
                                                {{ $description }}
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            @foreach ($existingActions as $action)
                                <td class="px-4 py-3 text-center">
                                    @if (isset($actions[$action]))
                                        @php $perm = $actions[$action]; @endphp
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $perm->name }}"
                                               :checked="isChecked('{{ $perm->name }}')"
                                               @change="toggle('{{ $perm->name }}')"
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    @else
                                        <span class="text-gray-300">–</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox"
                                       :checked="allChecked"
                                       @change="toggleAll()"
                                       class="rounded border-gray-300 text-gray-500 shadow-sm focus:ring-gray-400"
                                       title="Alle in diesem Modul">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <script>
        function rolesForm() {
            return {
                checkedPerms: new Set(@json($rolePermissions->toArray())),
            };
        }
        </script>
    @endif
</div>

{{-- Buttons --}}
<div class="flex items-center justify-end gap-3 mt-6">
    <a href="{{ route('roles.index') }}"
       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
        Abbrechen
    </a>
    @if (!$isSuperAdmin)
    <x-primary-button>
        {{ isset($role) ? 'Speichern' : 'Rolle anlegen' }}
    </x-primary-button>
    @endif
</div>
