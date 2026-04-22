{{-- Gemeinsames Formular-Partial für create und edit --}}

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

    {{-- Artikel / Betreff --}}
    <div class="sm:col-span-2">
        <x-input-label for="subject" value="Artikel / Bezeichnung *" />
        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full"
                      value="{{ old('subject', $order->subject ?? '') }}" required />
        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
    </div>

    {{-- Anzahl --}}
    <div>
        <x-input-label for="quantity" value="Anzahl *" />
        <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full"
                      value="{{ old('quantity', $order->quantity ?? 1) }}" required />
        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
    </div>

    {{-- Gesamtbetrag (Brutto) --}}
    <div>
        <x-input-label for="price_gross" value="Gesamtbetrag Brutto (€) *" />
        <x-text-input id="price_gross" name="price_gross" type="text" class="mt-1 block w-full"
                      placeholder="z.B. 199,90"
                      value="{{ old('price_gross', isset($order) ? number_format($order->price_gross, 2, ',', '') : '') }}" required />
        <x-input-error :messages="$errors->get('price_gross')" class="mt-2" />
    </div>

    {{-- Bestelldatum --}}
    <div>
        <x-input-label for="order_date" value="Bestelldatum" />
        <x-text-input id="order_date" name="order_date" type="date" class="mt-1 block w-full"
                      value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : now()->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('order_date')" class="mt-2" />
    </div>

    {{-- Haushaltsjahr --}}
    <div>
        <x-input-label for="budget_year" value="Haushaltsjahr *" />
        <select id="budget_year" name="budget_year"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required>
            @foreach ($availableBudgetYears as $yr)
                <option value="{{ $yr }}"
                    {{ old('budget_year', $order->budget_year ?? $defaultBudgetYear) == $yr ? 'selected' : '' }}>
                    {{ $yr }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">Nur {{ $availableBudgetYears[0] }}–{{ end($availableBudgetYears) }} möglich</p>
        <x-input-error :messages="$errors->get('budget_year')" class="mt-2" />
    </div>

    {{-- Status --}}
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}"
                    {{ old('status', $order->status ?? 1) == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    {{-- Händler (Autocomplete) --}}
    <div class="sm:col-span-2"
         x-data="{
             open: false,
             search: '{{ old('vendor_id', $order->vendor_id ?? '') ? $vendors->firstWhere('id', old('vendor_id', $order->vendor_id ?? ''))?->firmenname ?? '' : '' }}',
             selectedId: '{{ old('vendor_id', $order->vendor_id ?? '') }}',
             vendors: {{ Js::from($vendors->map(fn($v) => ['id' => $v->id, 'name' => $v->firmenname])) }},
             get filtered() {
                 if (this.search.trim() === '') return this.vendors;
                 return this.vendors.filter(v => v.name.toLowerCase().includes(this.search.toLowerCase()));
             },
             select(vendor) {
                 this.selectedId = vendor.id;
                 this.search = vendor.name;
                 this.open = false;
             },
             clear() {
                 this.selectedId = '';
                 this.search = '';
                 this.open = false;
             }
         }"
         @click.outside="open = false"
    >
        <div class="flex items-center justify-between">
            <x-input-label for="vendor_search" value="Händler" />
            <a href="{{ route('dienstleister.create') }}" target="_blank"
               class="text-xs text-indigo-600 hover:text-indigo-800">+ Neuen Händler anlegen</a>
        </div>

        <input type="hidden" name="vendor_id" :value="selectedId">

        <div class="relative mt-1">
            <input
                id="vendor_search"
                type="text"
                x-model="search"
                @focus="open = true"
                @input="open = true; selectedId = ''"
                @keydown.escape="open = false"
                placeholder="Händler suchen..."
                autocomplete="off"
                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
            >
            <button x-show="search !== ''" @click="clear()" type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <ul x-show="open && filtered.length > 0" x-cloak
                class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto">
                <li>
                    <button type="button" @click="clear()"
                            class="w-full text-left px-4 py-2 text-sm text-gray-400 hover:bg-gray-50">
                        – Kein Händler –
                    </button>
                </li>
                <template x-for="vendor in filtered" :key="vendor.id">
                    <li>
                        <button type="button" @click="select(vendor)"
                                :class="selectedId == vendor.id ? 'bg-indigo-50 text-indigo-700' : 'text-gray-900 hover:bg-gray-50'"
                                class="w-full text-left px-4 py-2 text-sm">
                            <span x-text="vendor.name"></span>
                        </button>
                    </li>
                </template>
                <li x-show="filtered.length === 0">
                    <span class="block px-4 py-2 text-sm text-gray-400">Kein Ergebnis</span>
                </li>
            </ul>
        </div>

        <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
    </div>

    {{-- Kostenstelle --}}
    <div>
        <x-input-label for="cost_center_id" value="Kostenstelle *" />
        <select id="cost_center_id" name="cost_center_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required>
            <option value="">– Bitte wählen –</option>
            @foreach ($costCenters as $kst)
                <option value="{{ $kst->id }}"
                    {{ old('cost_center_id', $order->cost_center_id ?? '') == $kst->id ? 'selected' : '' }}>
                    {{ $kst->number }}{{ $kst->description ? ' – ' . $kst->description : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('cost_center_id')" class="mt-2" />
    </div>

    {{-- Sachkonto --}}
    <div>
        <x-input-label for="account_code_id" value="Sachkonto *" />
        <select id="account_code_id" name="account_code_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                required>
            <option value="">– Bitte wählen –</option>
            @foreach ($accountCodes as $acc)
                <option value="{{ $acc->id }}"
                    {{ old('account_code_id', $order->account_code_id ?? '') == $acc->id ? 'selected' : '' }}>
                    {{ $acc->code }}{{ $acc->description ? ' – ' . $acc->description : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('account_code_id')" class="mt-2" />
    </div>

    {{-- Bemerkungen --}}
    <div class="sm:col-span-2">
        <x-input-label for="bemerkungen" value="Bemerkungen" />
        <textarea id="bemerkungen" name="bemerkungen" rows="4"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('bemerkungen', $order->bemerkungen ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('bemerkungen')" class="mt-2" />
    </div>

</div>
