<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('vertragsmanagement.index') }}" class="text-gray-400 hover:text-gray-600">← Verträge</a>
                <h2 class="font-semibold text-xl text-gray-800">{{ $vertrag->name }}</h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $vertrag->getStatusColor() }}">{{ $vertrag->getStatusLabel() }}</span>
            </div>
            @can('vertragsmanagement.edit')
                <div class="flex items-center gap-2">
                    <a href="{{ route('vertragsmanagement.edit', $vertrag) }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">Bearbeiten</a>
                    <form method="POST" action="{{ route('vertragsmanagement.destroy', $vertrag) }}"
                          onsubmit="return confirm('Vertrag „{{ $vertrag->name }}“ und alle Dokumente wirklich löschen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-white border border-red-200 rounded-md text-xs font-medium text-red-600 hover:bg-red-50">Löschen</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($vertrag->isInErinnerungsphase())
                <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 flex items-center gap-3">
                    <span class="text-amber-500 text-xl">⏰</span>
                    <p class="text-sm text-amber-800">
                        Dieser Vertrag ist in der <strong>Erinnerungsphase</strong> – wöchentliche Erinnerungen werden an
                        <strong>{{ $vertrag->getEmpfaengerEmail() ?? '—' }}</strong> gesendet.
                    </p>
                </div>
            @endif

            {{-- Stammdaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Vertragsdaten</h3>
                </div>
                <dl class="p-6 divide-y divide-gray-100 text-sm">
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Dienstleister</dt>
                        <dd>@if($vertrag->dienstleister)<a href="{{ route('dienstleister.show', $vertrag->dienstleister) }}" class="text-indigo-600 hover:underline">{{ $vertrag->dienstleister->firmenname }}</a>@else – @endif</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Vertragsbeginn</dt><dd>{{ $vertrag->vertragsbeginn?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Vertragsende</dt><dd>{{ $vertrag->vertragsende?->format('d.m.Y') ?? 'unbefristet' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Kündigungsfrist</dt><dd>{{ $vertrag->kuendigungsfrist_monate ? $vertrag->kuendigungsfrist_monate . ' Monat(e)' : '—' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Spätester Kündigungstermin</dt>
                        <dd class="{{ $vertrag->getKuendigungsstichtag() && $vertrag->getKuendigungsstichtag()->isPast() ? 'text-red-600 font-medium' : '' }}">{{ $vertrag->getKuendigungsstichtag()?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Erinnerung Vorlauf</dt><dd>{{ $vertrag->erinnerung_vorlauf_wochen }} Wochen vor Ende</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Erinnerung ab</dt><dd>{{ $vertrag->getErinnerungsstart()?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Empfänger-E-Mail</dt>
                        <dd>{{ $vertrag->benachrichtigungs_email ?: $vertrag->getEmpfaengerEmail() }}
                            @unless($vertrag->benachrichtigungs_email)<span class="text-xs text-gray-400">(Fallback)</span>@endunless</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-52 font-medium text-gray-500">Letzte Erinnerung</dt><dd>{{ $vertrag->last_reminder_sent_at?->format('d.m.Y H:i') ?? 'noch keine' }}</dd></div>
                    @if($vertrag->notizen)
                        <div class="py-2"><dt class="font-medium text-gray-500 mb-1">Notizen</dt><dd class="text-gray-700 whitespace-pre-line">{{ $vertrag->notizen }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Dokumente --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Dokumente
                        @if($vertrag->dokumente->isNotEmpty())
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">{{ $vertrag->dokumente->count() }}</span>
                        @endif
                    </h3>
                </div>

                @if($vertrag->dokumente->isEmpty())
                    <p class="px-6 py-5 text-sm text-gray-400">Keine Dokumente hinterlegt.</p>
                @else
                    <ul class="divide-y divide-gray-50">
                        @foreach($vertrag->dokumente as $dok)
                            <li class="px-6 py-3 flex items-center justify-between gap-3">
                                <a href="{{ route('vertragsmanagement.dokumente.download', $dok) }}"
                                   class="text-sm text-indigo-600 hover:underline truncate">📄 {{ $dok->dateiname }}</a>
                                <div class="flex items-center gap-4 shrink-0">
                                    <span class="text-xs text-gray-400">{{ $dok->groesse_lesbar }}</span>
                                    @can('vertragsmanagement.edit')
                                        <form method="POST" action="{{ route('vertragsmanagement.dokumente.destroy', $dok) }}"
                                              onsubmit="return confirm('Dokument löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                        </form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('vertragsmanagement.edit')
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <form method="POST" action="{{ route('vertragsmanagement.dokumente.store', $vertrag) }}"
                              enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                            @csrf
                            <input name="dokumente[]" type="file" accept="application/pdf" multiple required
                                   class="text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <x-primary-button type="submit">Hochladen</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->get('dokumente.0')" class="mt-1" />
                    </div>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
