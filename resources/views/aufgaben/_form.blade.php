<div class="p-6 space-y-6"
     x-data="{
         zuweisungen: {{ Js::from(
             isset($aufgabe) && $aufgabe !== null
                 ? $aufgabe->zuweisungen->map(fn($z) => [
                     'gruppe_id' => $z->gruppe_id ?? '',
                     'admin_user_id' => $z->admin_user_id ?? '',
                     'stellvertreter_user_id' => $z->stellvertreter_user_id ?? '',
                 ])->values()->toArray()
                 : []
         ) }},
         addZuweisung() {
             this.zuweisungen.push({ gruppe_id: '', admin_user_id: '', stellvertreter_user_id: '' });
         },
         removeZuweisung(index) {
             this.zuweisungen.splice(index, 1);
         }
     }">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Name --}}
    <div>
        <x-input-label for="name" value="Aufgabenbezeichnung *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            value="{{ old('name', $aufgabe?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    {{-- Übergeordnete Aufgabe --}}
    <div>
        <x-input-label for="parent_id" value="Übergeordnete Aufgabe" />
        <select id="parent_id" name="parent_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">— Keine (Root-Aufgabe) —</option>
            @foreach($alleAufgaben as $a)
                <option value="{{ $a['id'] }}"
                    @selected(old('parent_id', $aufgabe?->parent_id ?? $selectedParent?->id) == $a['id'])>
                    {{ str_repeat('— ', $a['depth']) }}{{ $a['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Zuweisungen --}}
    <div>
        <x-input-label value="Zuweisungen (Gruppe / Admin / Stellvertreter)" />
        <div class="mt-2 border border-gray-200 rounded-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gruppe</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellvertreter</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(zuw, index) in zuweisungen" :key="index">
                        <tr>
                            <td class="px-3 py-2">
                                <select :name="'zuweisungen[' + index + '][gruppe_id]'"
                                        x-model="zuw.gruppe_id"
                                        class="block w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— Keine —</option>
                                    @foreach($gruppen as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select :name="'zuweisungen[' + index + '][admin_user_id]'"
                                        x-model="zuw.admin_user_id"
                                        class="block w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— Kein Admin —</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select :name="'zuweisungen[' + index + '][stellvertreter_user_id]'"
                                        x-model="zuw.stellvertreter_user_id"
                                        class="block w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— Kein Stellvertreter —</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" @click="removeZuweisung(index)"
                                        class="text-red-400 hover:text-red-600 font-bold text-lg leading-none">×</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="zuweisungen.length === 0">
                        <td colspan="4" class="px-3 py-4 text-center text-gray-400 text-sm">
                            Noch keine Zuweisungen.
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                <button type="button" @click="addZuweisung()"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    + Zuweisung hinzufügen
                </button>
            </div>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <a href="{{ route('aufgaben.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Abbrechen</a>
        <x-primary-button>Speichern</x-primary-button>
    </div>
</div>
