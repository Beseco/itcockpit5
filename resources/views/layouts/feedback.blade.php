<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IT-Support Bewertung – {{ config('app.name', 'IT Cockpit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gradient-to-br from-indigo-50 via-white to-blue-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow-sm border-b border-gray-100">
            <div class="max-w-2xl mx-auto px-4 py-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                </div>
                <div>
                    <span class="font-semibold text-gray-800">IT Cockpit</span>
                    <span class="text-gray-400 mx-1">·</span>
                    <span class="text-gray-500 text-sm">Support-Bewertung</span>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-start justify-center py-10 px-4">
            <div class="w-full max-w-2xl">
                {{ $slot }}
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-gray-400">
            <p>© {{ date('Y') }} {{ config('app.name', 'IT Cockpit') }} &nbsp;·&nbsp; Die Bewertung erfolgt anonym und kann nicht auf Personen oder Tickets zurückgeführt werden.</p>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
