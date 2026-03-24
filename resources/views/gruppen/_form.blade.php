<div class="p-6 space-y-6">
    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Name --}}
    <div>
        <x-input-label for="name" value="Gruppenname *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            value="{{ old('name', $gruppe?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    {{-- Übergeordnete Gruppe --}}
    <div>
        <x-input-label for="parent_id" value="Übergeordnete Gruppe" />
        <select id="parent_id" name="parent_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">— Keine (Root-Gruppe) —</option>
            @foreach($allGruppen as $g)
                <option value="{{ $g->id }}"
                    @selected(old('parent_id', $gruppe?->parent_id ?? $selectedParent?->id) == $g->id)>
                    {{ $g->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
    </div>

    {{-- Sortierung --}}
    <div>
        <x-input-label for="sort_order" value="Sortierung" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-32"
            value="{{ old('sort_order', $gruppe?->sort_order ?? 0) }}" />
    </div>

    {{-- Vorgesetzter --}}
    <div>
        <x-input-label for="vorgesetzter_user_id" value="Vorgesetzter/in" />
        <select id="vorgesetzter_user_id" name="vorgesetzter_user_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">— Kein/e Vorgesetzte/r —</option>
            @foreach($allUsers as $u)
                <option value="{{ $u->id }}"
                    @selected(old('vorgesetzter_user_id', $gruppe?->vorgesetzter_user_id) == $u->id)>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Der/Die Vorgesetzte kann Admin und Stellvertreter bei Aufgaben seiner/ihrer Gruppe ändern.</p>
        <x-input-error :messages="$errors->get('vorgesetzter_user_id')" class="mt-1" />
    </div>

    {{-- Rollen --}}
    <div>
        <x-input-label value="Rollen (werden an Mitglieder vererbt)" />
        <div class="mt-2 grid grid-cols-2 gap-2">
            @foreach($roles as $role)
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                           @checked(in_array($role->id, old('role_ids', $gruppe?->roles->pluck('id')->toArray() ?? [])))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ $role->name }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- Mitglieder (nur bei Edit) --}}
    @if(isset($gruppe) && $gruppe !== null)
    <div>
        <x-input-label value="Mitglieder" />
        <div class="mt-2 grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
            @foreach($allUsers as $user)
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                           @checked(in_array($user->id, old('user_ids', $gruppe->users->pluck('id')->toArray())))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ $user->name }}
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-1">Mitglieder erben automatisch die Rollen dieser Gruppe.</p>
    </div>
    @endif

    {{-- Buttons --}}
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <a href="{{ route('gruppen.index') }}"
           class="text-sm text-gray-600 hover:text-gray-900">Abbrechen</a>
        <x-primary-button>Speichern</x-primary-button>
    </div>
</div>
