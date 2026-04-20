<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision abgeschlossen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

<div class="max-w-lg w-full">
    <div class="bg-indigo-700 rounded-t-xl px-8 py-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-1">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-xl font-bold text-white">{{ $abteilung->anzeigename }}</h1>
    </div>
    <div class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200 px-8 py-10 text-center">
        <div class="text-5xl mb-5">✅</div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Vielen Dank!</h2>
        <p class="text-gray-500 leading-relaxed">
            Ihre Rückmeldung wurde erfolgreich übermittelt.<br>
            Die IT-Abteilung wird sich darum kümmern.
        </p>
    </div>
    <p class="text-center text-xs text-gray-400 mt-5">IT Cockpit &middot; automatisch generiert</p>
</div>

</body>
</html>
