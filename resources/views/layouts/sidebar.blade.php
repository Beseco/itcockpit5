@php
    use App\Models\Module;
    $activeModules = Module::where('is_active', true)->pluck('name')->flip()->toArray();
    $user = Auth::user();
    $isSuperAdmin = $user->isSuperAdmin();

    $canSee = fn(string $module, string $permission) =>
        isset($activeModules[$module]) && ($isSuperAdmin || $user->hasPermissionTo($permission));

    $inOrdersSection = request()->routeIs('orders.*')
        || request()->routeIs('cost-centers.*')
        || request()->routeIs('account-codes.*');

    $inUsersSection = request()->routeIs('users.*')
        || request()->routeIs('roles.*')
        || request()->routeIs('gruppen.*');
@endphp

<div class="flex-1 flex flex-col min-h-0">
    <!-- Logo -->
    <div class="flex items-center h-16 flex-shrink-0 px-4 bg-gray-900">
        <h1 class="text-white text-xl font-bold">IT Cockpit</h1>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            Dashboard
        </a>

        {{-- Ankündigungen --}}
        @if($canSee('announcements', 'announcements.view'))
        <a href="{{ route('announcements.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('announcements.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('announcements.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
            </svg>
            Ankündigungen
        </a>
        @endif

        {{-- Applikationen --}}
        @if($canSee('applikationen', 'applikationen.view'))
        <a href="{{ route('applikationen.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('applikationen.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('applikationen.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 15V5.25m19.5 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V5.25" />
            </svg>
            Applikationen
        </a>
        @endif

        {{-- Bestellungen (aufklappbar) --}}
        @if($canSee('orders', 'orders.view'))
        <div x-data="{ open: {{ $inOrdersSection ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ $inOrdersSection ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ $inOrdersSection ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
                <span class="flex-1 text-left">Bestellungen</span>
                <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div x-show="open" x-cloak class="mt-0.5 ml-3 pl-3 border-l border-gray-600 space-y-0.5">
                <a href="{{ route('orders.index') }}"
                   class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('orders.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                    Bestellungen
                </a>
                @if($isSuperAdmin || $user->hasPermissionTo('cost-centers.view'))
                <a href="{{ route('cost-centers.index') }}"
                   class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('cost-centers.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                    Kostenstellen
                </a>
                @endif
                @if($isSuperAdmin || $user->hasPermissionTo('account-codes.view'))
                <a href="{{ route('account-codes.index') }}"
                   class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('account-codes.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                    Sachkonten
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Dienstleister --}}
        @if($canSee('dienstleister', 'dienstleister.view'))
        <a href="{{ route('dienstleister.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('dienstleister.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('dienstleister.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            Dienstleister
        </a>
        @endif

        {{-- Erinnerungsmails --}}
        @if($canSee('reminders', 'reminders.view'))
        <a href="{{ route('reminders.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('reminders.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('reminders.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Erinnerungsmails
        </a>
        @endif

        {{-- Rollen & Aufgaben (nicht im Admin-Bereich) --}}
        @if($canSee('aufgaben', 'base.aufgaben.view'))
        <a href="{{ route('aufgaben.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('aufgaben.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('aufgaben.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            Rollen &amp; Aufgaben
        </a>
        @endif

        {{-- HookManager-Module (Haushaltsplanung, Network, etc.) --}}
        @if(isset($moduleNavItems))
            @foreach($moduleNavItems as $item)
            <a href="{{ route($item['route']) }}"
               class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs($item['route']) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                {{ $item['label'] }}
            </a>
            @endforeach
        @endif

        {{-- Persönlicher Bereich --}}
        <a href="{{ route('personal.index') }}"
           class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('personal.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('personal.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            Persönlicher Bereich
        </a>

        {{-- Admin-Bereich --}}
        @if($isSuperAdmin || $user->can('base.users.view') || $user->can('base.roles.view') || $user->can('audit.view') || $user->can('base.modules.manage') || $user->can('base.gruppen.view') || $user->can('base.stellen.view') || $user->can('base.stellenbeschreibungen.view'))
        <div class="pt-3 mt-3 border-t border-gray-700">
            <p class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Administration</p>

            {{-- Audit Protokoll --}}
            @if(Route::has('audit-logs.index'))
            @can('audit.view')
            <a href="{{ route('audit-logs.index') }}"
               class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('audit-logs.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('audit-logs.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                Audit Protokoll
            </a>
            @endcan
            @endif

            {{-- Benutzerverwaltung (aufklappbar) --}}
            @if($isSuperAdmin || $user->can('base.users.view') || $user->can('base.roles.view') || $user->can('base.gruppen.view'))
            <div x-data="{ open: {{ $inUsersSection ? 'true' : 'false' }} }">
                <button @click="open = !open"
                        class="w-full group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ $inUsersSection ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ $inUsersSection ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="flex-1 text-left">Benutzerverwaltung</span>
                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open" x-cloak class="mt-0.5 ml-3 pl-3 border-l border-gray-600 space-y-0.5">
                    @can('base.users.view')
                    <a href="{{ route('users.index') }}"
                       class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('users.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                        Benutzerverwaltung
                    </a>
                    @endcan
                    @if($canSee('gruppen', 'base.gruppen.view'))
                    <a href="{{ route('gruppen.index') }}"
                       class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('gruppen.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                        Gruppenverwaltung
                    </a>
                    @endif
                    @can('base.roles.view')
                    <a href="{{ route('roles.index') }}"
                       class="group flex items-center px-2 py-1 text-sm rounded-md {{ request()->routeIs('roles.*') ? 'bg-gray-900 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                        Rollenverwaltung
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- Module --}}
            @can('base.modules.manage')
            <a href="{{ route('modules.index') }}"
               class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('modules.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('modules.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                Module
            </a>
            @endcan


            {{-- Stellenbeschreibungen --}}
            @if($canSee('stellenbeschreibungen', 'base.stellenbeschreibungen.view'))
            <a href="{{ route('stellenbeschreibungen.index') }}"
               class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md {{ request()->routeIs('stellenbeschreibungen.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('stellenbeschreibungen.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                Stellenbeschreibungen
            </a>
            @endif

        </div>
        @endif

        {{-- Altes IT-Cockpit --}}
        <div class="pt-3 mt-3 border-t border-gray-700">
            <a href="https://itcockpit.lra.lan/old"
               class="group flex items-center px-2 py-1.5 text-sm font-medium rounded-md text-gray-400 hover:bg-gray-700 hover:text-white">
                <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-500 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                Altes IT-Cockpit
            </a>
        </div>

    </nav>

    <!-- Footer -->
    <div class="flex-shrink-0 flex border-t border-gray-700 p-4">
        <div class="flex items-center w-full">
            <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="ml-3 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs font-medium text-gray-400 truncate">{{ Auth::user()->getRoleNames()->implode(', ') }}</p>
            </div>
        </div>
    </div>
</div>
