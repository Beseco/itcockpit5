{{-- Rekursive Baumzeile für Vorlagen (gespiegelt aus der OE-Hierarchie) --}}
@php $v = $vorlagenByAbteilung->get($abteilung->id); @endphp
<div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50"
     style="padding-left: {{ 1 + $depth * 1.5 }}rem">
    <div class="flex items-center gap-3 min-w-0">
        @if($depth > 0)
            <span class="text-gray-300 select-none">└</span>
        @endif
        <div class="min-w-0">
            <span class="font-medium text-gray-900">{{ $abteilung->name }}</span>
            @if($abteilung->kurzzeichen)
                <span class="ml-2 px-1.5 py-0.5 text-xs font-mono bg-gray-100 text-gray-600 rounded">{{ $abteilung->kurzzeichen }}</span>
            @endif
            @if($v)
                <span class="ml-1 px-1.5 py-0.5 text-xs bg-purple-50 text-purple-600 rounded">{{ $v->gruppen->count() }} Gruppe(n)</span>
                @unless($v->is_active)
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-gray-100 text-gray-500 rounded">Inaktiv</span>
                @endunless
            @else
                <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-50 text-amber-600 rounded">keine Vorlage</span>
            @endif
        </div>
    </div>
    <div class="inline-flex items-center gap-3 shrink-0">
        @if($v)
            @if($v->is_active)
                <a href="{{ route('onboarding.create', ['vorlage_id' => $v->id]) }}"
                   class="text-xs text-indigo-600 hover:text-indigo-800">Verwenden</a>
            @endif
            @can('module.onboarding.edit')
                <a href="{{ route('onboarding.vorlagen.edit', $v) }}"
                   class="text-xs text-gray-500 hover:text-gray-700">Bearbeiten</a>
            @endcan
        @endif
    </div>
</div>

@foreach($abteilung->children as $child)
    @include('onboarding::vorlagen._tree_row', ['abteilung' => $child, 'depth' => $depth + 1])
@endforeach
