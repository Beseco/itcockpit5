<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision abgeschlossen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
<div class="max-w-md w-full bg-white rounded-xl shadow p-10 text-center">
    <div class="text-6xl mb-6">✅</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">Revision abgeschlossen</h1>
    <p class="text-gray-600 mb-2">
        Vielen Dank! Die Revision für <strong>{{ $app->name }}</strong> wurde erfolgreich abgeschlossen.
    </p>
    <p class="text-gray-400 text-sm">
        Ihre Angaben wurden gespeichert. Die nächste Revision ist fällig am
        <strong>{{ $app->revision_date->format('d.m.Y') }}</strong>.
    </p>
    <p class="mt-6 text-xs text-gray-300">IT Cockpit · Automatisches Revisionssystem</p>
</div>
</body>
</html>
