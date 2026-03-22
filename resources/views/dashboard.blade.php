<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $iconPaths = [
            'cart'      => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
            'building'  => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
            'clock'     => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
            'megaphone' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46',
            'monitor'   => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 15V5.25m19.5 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V5.25',
            'checklist' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'briefcase' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z',
            'users'     => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
        ];
        $colorMap = [
            'indigo' => ['border' => 'border-indigo-500', 'bg' => 'bg-indigo-50', 'icon' => 'text-indigo-500', 'text' => 'text-indigo-700'],
            'blue'   => ['border' => 'border-blue-500',   'bg' => 'bg-blue-50',   'icon' => 'text-blue-500',   'text' => 'text-blue-700'],
            'amber'  => ['border' => 'border-amber-500',  'bg' => 'bg-amber-50',  'icon' => 'text-amber-500',  'text' => 'text-amber-700'],
            'green'  => ['border' => 'border-green-500',  'bg' => 'bg-green-50',  'icon' => 'text-green-500',  'text' => 'text-green-700'],
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ankündigungen --}}
            @if($announcements->isNotEmpty())
                <div class="space-y-4">
                    @foreach($announcements as $announcement)
                        <x-announcement-card :announcement="$announcement" />
                    @endforeach
                </div>
            @endif

            {{-- Modul-Kacheln --}}
            @if($tiles->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($tiles as $tile)
                        @php $c = $colorMap[$tile['color']] ?? $colorMap['indigo']; @endphp
                        <a href="{{ route($tile['route']) }}"
                           class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $c['border'] }} hover:{{ $c['bg'] }} transition-colors duration-150 group">
                            <div class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $tile['label'] }}
                                        </p>
                                        <p class="mt-1 text-sm font-semibold {{ $c['text'] }}">
                                            {{ $tile['stat'] }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-400 truncate">
                                            {{ $tile['description'] }}
                                        </p>
                                        @if(array_key_exists('scheduler_ok', $tile))
                                            <p class="mt-1 text-xs font-medium {{ $tile['scheduler_ok'] ? 'text-green-600' : 'text-red-500' }}">
                                                ● Scheduler {{ $tile['scheduler_ok'] ? 'aktiv' : 'nicht aktiv' }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="{{ $c['icon'] }} ml-3 flex-shrink-0">
                                        <svg class="h-8 w-8 opacity-70 group-hover:opacity-100 transition-opacity"
                                             fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="{{ $iconPaths[$tile['icon']] ?? $iconPaths['cart'] }}" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- HookManager Widgets (für externe Module) --}}
            @if($widgets->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($widgets as $widget)
                        <x-module-widget :viewPath="$widget['viewPath']" />
                    @endforeach
                </div>
            @endif

            @if($tiles->isEmpty() && $widgets->isEmpty() && $announcements->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500">
                        Willkommen im IT Cockpit. Noch keine aktiven Module oder Ankündigungen.
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
