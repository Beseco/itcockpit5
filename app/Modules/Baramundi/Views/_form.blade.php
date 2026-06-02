{{-- Wiederverwendetes Formular-Partial für create und edit --}}
@php $isEdit = isset($pkg); @endphp

{{-- Name --}}
<div>
    <x-input-label for="name" value="Anzeigename *" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  value="{{ old('name', $pkg->name ?? '') }}"
                  placeholder="z.B. TeamViewer Host" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-1" />
</div>

{{-- Server + Pfad --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="server_name" value="Servername *" />
        <x-text-input id="server_name" name="server_name" type="text" class="mt-1 block w-full"
                      value="{{ old('server_name', $pkg->server_name ?? '') }}"
                      placeholder="Bara-01.lra.lan" required />
        <p class="mt-1 text-xs text-gray-400">Hostname oder IP-Adresse des Servers (ohne \\)</p>
        <x-input-error :messages="$errors->get('server_name')" class="mt-1" />
    </div>
    <div class="sm:col-span-1">
        <x-input-label for="share_path" value="SMB-Freigabepfad *" />
        <x-text-input id="share_path" name="share_path" type="text" class="mt-1 block w-full"
                      value="{{ old('share_path', $pkg->share_path ?? '') }}"
                      placeholder="dip$\ManagedSoftware\source\TeamViewer\TeamViewerHost\15.x-x64" required />
        <p class="mt-1 text-xs text-gray-400">Pfad relativ zur Freigabe, ohne führende \\Server\</p>
        <x-input-error :messages="$errors->get('share_path')" class="mt-1" />
    </div>
</div>

{{-- Optionen --}}
<div class="space-y-2">
    <x-input-label value="Optionen" />
    <div class="flex items-center gap-2">
        <input type="checkbox" id="enabled" name="enabled" value="1"
               @checked(old('enabled', $pkg->enabled ?? true))
               class="rounded border-gray-300 text-indigo-600">
        <label for="enabled" class="text-sm text-gray-700">Paket aktiv (wird beim automatischen Scan geprüft)</label>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" id="email_enabled" name="email_enabled" value="1"
               @checked(old('email_enabled', $pkg->email_enabled ?? true))
               class="rounded border-gray-300 text-indigo-600">
        <label for="email_enabled" class="text-sm text-gray-700">E-Mail-Benachrichtigung bei neuer Version senden</label>
    </div>
</div>

{{-- Download --}}
<div class="space-y-3">
    <div>
        <x-input-label for="download_type" value="Download-Methode" />
        <select id="download_type" name="download_type"
                x-model="downloadType"
                class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="none">Kein automatischer Download</option>
            <option value="http">HTTP/HTTPS Download</option>
            <option value="powershell">PowerShell-Skript</option>
            <option value="batch">Batch-Datei</option>
        </select>
    </div>

    <div x-show="downloadType === 'http'" style="display:none">
        <x-input-label for="download_url" value="Download-URL" />
        <x-text-input id="download_url" name="download_url" type="url" class="mt-1 block w-full"
                      value="{{ old('download_url', $pkg->download_url ?? '') }}"
                      placeholder="https://example.com/downloads/{version}/setup.msi" />
        <p class="mt-1 text-xs text-gray-400">Platzhalter <code class="bg-gray-100 px-1 rounded">{version}</code> wird durch die erkannte Version ersetzt.</p>
        <x-input-error :messages="$errors->get('download_url')" class="mt-1" />
    </div>

    <div x-show="downloadType === 'powershell' || downloadType === 'batch'" style="display:none">
        <x-input-label for="download_command" value="Befehl / Skriptpfad" />
        <textarea id="download_command" name="download_command" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono"
                  placeholder="C:\Scripts\Download-TeamViewer.ps1 -Version {version} -Path &quot;{unc_path}&quot;">{{ old('download_command', $pkg->download_command ?? '') }}</textarea>
        <p class="mt-1 text-xs text-gray-400">
            Platzhalter: <code class="bg-gray-100 px-1 rounded">{version}</code>,
            <code class="bg-gray-100 px-1 rounded">{unc_path}</code>,
            <code class="bg-gray-100 px-1 rounded">{package_name}</code>
        </p>
        <x-input-error :messages="$errors->get('download_command')" class="mt-1" />
    </div>
</div>

{{-- Notizen --}}
<div>
    <x-input-label for="notes" value="Notizen (optional)" />
    <textarea id="notes" name="notes" rows="2"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
              placeholder="Lizenzhinweise, Provider-Informationen, …">{{ old('notes', $pkg->notes ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
</div>
