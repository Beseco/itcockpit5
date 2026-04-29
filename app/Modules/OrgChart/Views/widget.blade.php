@php
    $aktiv = \App\Modules\OrgChart\Models\OrgVersion::where('status', 'aktiv')->first();
    $total = \App\Modules\OrgChart\Models\OrgVersion::count();
@endphp
@if($aktiv || $total > 0)
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Organigramm</h3>
        <a href="{{ route('orgchart.index') }}" class="text-xs text-indigo-600 hover:underline">Alle anzeigen</a>
    </div>
    @if($aktiv)
    <div class="flex items-center gap-2 mb-2">
        <span class="w-2 h-2 rounded-full bg-green-500"></span>
        <span class="text-sm font-medium text-gray-800">{{ $aktiv->name }}</span>
    </div>
    <div class="flex gap-4 text-xs text-gray-500">
        <span>{{ $aktiv->group_count_val ?? 0 }} Gruppen</span>
        <span>{{ number_format($aktiv->total_headcount_val ?? 0, 1, ',', '') }} FTE</span>
    </div>
    <a href="{{ route('orgchart.show', $aktiv) }}"
       class="mt-3 inline-flex items-center text-xs text-indigo-600 hover:underline">
        Grafisch anzeigen →
    </a>
    @else
    <p class="text-xs text-gray-400">{{ $total }} Version(en) vorhanden – keine aktiv.</p>
    @endif
</div>
@endif
