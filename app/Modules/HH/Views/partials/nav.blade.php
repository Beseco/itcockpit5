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
            <a href="{{ route('hh.help') }}"
               title="Hilfe & Anleitung"
               class="ml-auto inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition flex-shrink-0">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            </a>
        </nav>
    </div>
</div>
