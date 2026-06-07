@php
    $route = request()->route()?->getName() ?? '';
    $isBenutzer    = str_starts_with($route, 'adusers.') && !str_starts_with($route, 'adusers.offboarding') && !str_starts_with($route, 'adusers.settings');
    $isOnboarding  = str_starts_with($route, 'onboarding.');
    $isOffboarding = str_starts_with($route, 'adusers.offboarding');
    $isSettings    = str_starts_with($route, 'adusers.settings') || str_starts_with($route, 'onboarding.settings');
@endphp
<div class="border-b border-gray-200 bg-white sticky top-0 z-10">
    <nav class="px-4 sm:px-6 lg:px-8 flex gap-0 text-sm overflow-x-auto">

        <a href="{{ route('adusers.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-3 border-b-2 font-medium whitespace-nowrap transition
                  {{ $isBenutzer ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Benutzer
        </a>

        @canany(['module.onboarding.view', 'module.onboarding.edit'])
        <a href="{{ route('onboarding.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-3 border-b-2 font-medium whitespace-nowrap transition
                  {{ $isOnboarding ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Onboarding
        </a>
        @endcanany

        <a href="{{ route('adusers.offboarding.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-3 border-b-2 font-medium whitespace-nowrap transition
                  {{ $isOffboarding ? 'border-orange-500 text-orange-700' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Offboarding
        </a>

        @canany(['module.adusers.config', 'module.onboarding.config'])
        <a href="{{ route('adusers.settings') }}"
           class="inline-flex items-center gap-1.5 px-4 py-3 border-b-2 font-medium whitespace-nowrap transition
                  {{ $isSettings ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Einstellungen
        </a>
        @endcanany

    </nav>
</div>
