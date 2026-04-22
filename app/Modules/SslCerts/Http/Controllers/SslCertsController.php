<?php

namespace App\Modules\SslCerts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Server\Models\Server;
use App\Modules\SslCerts\Models\SslCertificate;
use Illuminate\Http\Request;

class SslCertsController extends Controller
{
    public function index()
    {
        $certs = SslCertificate::with('responsibleUser')->orderBy('valid_to')->get();
        return view('sslcerts::index', compact('certs'));
    }

    public function create()
    {
        $users   = User::where('is_active', true)->orderBy('name')->get();
        $servers = Server::orderBy('name')->get();
        return view('sslcerts::create', compact('users', 'servers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'upload_type' => ['required', 'in:p12,pem,url'],
        ]);

        if ($request->input('upload_type') === 'p12') {
            return $this->storeFromP12($request);
        }

        if ($request->input('upload_type') === 'url') {
            return $this->storeFromUrl($request);
        }

        return $this->storeFromPem($request);
    }

    public function show(SslCertificate $cert)
    {
        $cert->load('responsibleUser', 'servers');
        return view('sslcerts::show', compact('cert'));
    }

    public function edit(SslCertificate $cert)
    {
        $cert->load('servers');
        $users      = User::where('is_active', true)->orderBy('name')->get();
        $servers    = Server::orderBy('name')->get();
        $serverIds  = $cert->servers->pluck('id')->toArray();
        return view('sslcerts::edit', compact('cert', 'users', 'servers', 'serverIds'));
    }

    public function update(Request $request, SslCertificate $cert)
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'doc_url'             => ['nullable', 'url', 'max:500'],
            'urls'                => ['nullable', 'array'],
            'urls.*'              => ['url', 'max:500'],
            'servers'             => ['nullable', 'array'],
            'servers.*'           => ['integer', 'exists:servers,id'],
        ]);

        $cert->update([
            'name'                => $request->input('name'),
            'description'         => $request->input('description'),
            'responsible_user_id' => $request->input('responsible_user_id'),
            'doc_url'             => $request->input('doc_url'),
            'urls'                => $request->input('urls') ?: null,
        ]);

        $cert->servers()->sync($request->input('servers', []));

        return redirect()->route('sslcerts.show', $cert)
            ->with('success', 'Zertifikat „' . $cert->name . '" wurde aktualisiert.');
    }

    public function destroy(SslCertificate $cert)
    {
        $name = $cert->name;
        $cert->delete();

        return redirect()->route('sslcerts.index')
            ->with('success', 'Zertifikat „' . $name . '" wurde gelöscht.');
    }

    public function download(SslCertificate $cert, string $type)
    {
        if ($type === 'cert') {
            $filename = $this->safeFilename($cert->name) . '.pem';
            return response($cert->cert_pem, 200, [
                'Content-Type'        => 'application/x-pem-file',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        if ($type === 'key' && $cert->private_key) {
            $filename = $this->safeFilename($cert->name) . '.key';
            return response($cert->private_key, 200, [
                'Content-Type'        => 'application/x-pem-file',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        abort(404);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function storeFromUrl(Request $request)
    {
        $request->validate([
            'cert_url' => ['required', 'url', 'max:500'],
        ]);

        $url  = $request->input('cert_url');
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?? 443;

        if (!$host) {
            return back()->withInput()
                ->withErrors(['cert_url' => 'Ungültige URL – kein Hostname erkannt.']);
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'SNI_enabled'       => true,
                'peer_name'         => $host,
            ],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return back()->withInput()
                ->withErrors(['cert_url' => "Verbindung zu {$host}:{$port} fehlgeschlagen: {$errstr}"]);
        }

        $params = stream_context_get_params($socket);
        fclose($socket);

        $certResource = $params['options']['ssl']['peer_certificate'] ?? null;

        if (!$certResource) {
            return back()->withInput()
                ->withErrors(['cert_url' => 'Es konnte kein Zertifikat von der URL abgerufen werden.']);
        }

        openssl_x509_export($certResource, $certPem);

        if (!$certPem) {
            return back()->withInput()
                ->withErrors(['cert_url' => 'Das Zertifikat konnte nicht exportiert werden.']);
        }

        return $this->saveCertificate($request, $certPem, null);
    }

    private function storeFromP12(Request $request)
    {
        $request->validate([
            'p12_file' => ['required', 'file', 'max:4096'],
        ]);

        $p12Data = file_get_contents($request->file('p12_file')->getRealPath());
        $pin     = $request->input('p12_pin', '');

        $certs = [];
        if (!openssl_pkcs12_read($p12Data, $certs, $pin)) {
            return back()
                ->withInput($request->except('p12_pin'))
                ->withErrors(['p12_pin' => 'Das P12-Zertifikat konnte nicht gelesen werden. Bitte Transport-PIN prüfen.']);
        }

        $certPem    = $certs['cert']  ?? null;
        $privateKey = $certs['pkey']  ?? null;

        if (!$certPem) {
            return back()
                ->withInput($request->except('p12_pin'))
                ->withErrors(['p12_file' => 'Das P12-Zertifikat enthält kein gültiges Zertifikat.']);
        }

        return $this->saveCertificate($request, $certPem, $privateKey);
    }

    private function storeFromPem(Request $request)
    {
        $request->validate([
            'pem_cert' => ['required', 'file', 'max:4096'],
            'pem_key'  => ['nullable', 'file', 'max:4096'],
        ]);

        $certPem = file_get_contents($request->file('pem_cert')->getRealPath());

        if (!openssl_x509_parse($certPem)) {
            return back()
                ->withInput()
                ->withErrors(['pem_cert' => 'Die Datei enthält kein gültiges PEM-Zertifikat.']);
        }

        $privateKey = null;
        if ($request->hasFile('pem_key')) {
            $privateKey = file_get_contents($request->file('pem_key')->getRealPath());
            if (!str_contains($privateKey, '-----BEGIN')) {
                return back()
                    ->withInput()
                    ->withErrors(['pem_key' => 'Die Datei enthält keinen gültigen PEM-Key.']);
            }
        }

        return $this->saveCertificate($request, $certPem, $privateKey);
    }

    private function saveCertificate(Request $request, string $certPem, ?string $privateKey)
    {
        $parsed = openssl_x509_parse($certPem);

        $subjectCn  = $parsed['subject']['CN']  ?? null;
        $subjectO   = $parsed['subject']['O']   ?? null;
        $subjectOu  = $parsed['subject']['OU']  ?? null;
        $issuerCn   = $parsed['issuer']['CN']   ?? null;
        $issuerO    = $parsed['issuer']['O']    ?? null;
        $serial     = $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? null);
        $validFrom  = isset($parsed['validFrom_time_t'])  ? \Carbon\Carbon::createFromTimestamp($parsed['validFrom_time_t'])  : null;
        $validTo    = isset($parsed['validTo_time_t'])    ? \Carbon\Carbon::createFromTimestamp($parsed['validTo_time_t'])    : null;

        $sanRaw = $parsed['extensions']['subjectAltName'] ?? '';
        $sans   = [];
        if ($sanRaw) {
            foreach (explode(',', $sanRaw) as $entry) {
                $entry = trim($entry);
                if ($entry) $sans[] = $entry;
            }
        }

        $sha1   = openssl_x509_fingerprint($certPem, 'sha1')   ?: null;
        $sha256 = openssl_x509_fingerprint($certPem, 'sha256') ?: null;

        $cert = SslCertificate::create([
            'name'                => $request->input('name'),
            'description'         => $request->input('description'),
            'responsible_user_id' => $request->input('responsible_user_id') ?: null,
            'doc_url'             => $request->input('doc_url'),
            'urls'                => $request->input('urls') ?: null,
            'subject_cn'          => $subjectCn,
            'subject_o'           => $subjectO,
            'subject_ou'          => $subjectOu,
            'issuer_cn'           => $issuerCn,
            'issuer_o'            => $issuerO,
            'serial_number'       => $serial,
            'valid_from'          => $validFrom,
            'valid_to'            => $validTo,
            'san'                 => $sans ?: null,
            'fingerprint_sha1'    => $sha1,
            'fingerprint_sha256'  => $sha256,
            'cert_pem'            => $certPem,
            'private_key'         => $privateKey,
        ]);

        $cert->servers()->sync($request->input('servers', []));

        return redirect()->route('sslcerts.index')
            ->with('success', 'Zertifikat „' . $cert->name . '" wurde erfolgreich importiert.');
    }

    private function safeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    }
}
