@php
    use App\Modules\HH\Models\BudgetYearVersion;

    // Try to find a version ID from the current context or from the latest active budget year
    $navVersionId = $activeVersion->id
        ?? $version->id
        ?? BudgetYearVersion::where('is_active', true)
            ->latest('id')->value('id');

    $navItems = [
        ['label' => 'Dashboard',      'route' => 'hh.dashboard.index',    'pattern' => 'hh.dashboard.*'],
        ['label' => 'Haushaltsjahre', 'route' => 'hh.budget-years.index', 'pattern' => 'hh.budget-years.*'],
        ['label' => 'Kostenstellen',  'route' => 'hh.cost-centers.index', 'pattern' => 'hh.cost-centers.*'],
        ['label' => 'Sachkonten',     'route' => 'hh.accounts.index',     'pattern' => 'hh.accounts.*'],
        ['label' => 'Audit-Log',      'route' => 'hh.audit.index',        'pattern' => 'hh.audit.*'],
        ['label' => 'Import / Export','route' => 'hh.import.index',       'pattern' => 'hh.import.*'],
    ];
@endphp

<div class="bg-white border-b border-gray-200 mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-1 overflow-x-auto py-1" aria-label="Haushaltsplanung">
            @foreach($navItems as $item)
            @php $isActive = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="whitespace-nowrap px-3 py-2 text-sm font-medium rounded-md transition
                      {{ $isActive ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                {{ $item['label'] }}
            </a>
            @endforeach

            @if($navVersionId)
            <a href="{{ route('hh.versions.positions.index', $navVersionId) }}"
               class="whitespace-nowrap px-3 py-2 text-sm font-medium rounded-md transition
                      {{ request()->routeIs('hh.versions.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                Positionen
            </a>
            @endif
        </nav>
    </div>
</div>
