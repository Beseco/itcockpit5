@php
    $total = \App\Models\Stelle::count();
    $frei  = \App\Models\Stelle::whereNull('user_id')->count();
    $besetzt = $total - $frei;
@endphp

<div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Stellenplan</h3>
        <a href="{{ route('stellenplan.index') }}" class="text-xs text-indigo-600 hover:underline">Übersicht →</a>
    </div>
    <div class="grid grid-cols-3 gap-3 text-center">
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
            <div class="text-xs text-gray-500 mt-0.5">Stellen gesamt</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-green-600">{{ $besetzt }}</div>
            <div class="text-xs text-gray-500 mt-0.5">besetzt</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-amber-500">{{ $frei }}</div>
            <div class="text-xs text-gray-500 mt-0.5">FREI</div>
        </div>
    </div>
</div>
