<?php

namespace App\Modules\SslCerts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SslCerts\Models\SslCertificate;
use Illuminate\Http\Request;

class SslCertsController extends Controller
{
    public function index()
    {
        $certs = SslCertificate::orderBy('valid_to')->get();
        return view('sslcerts::index', compact('certs'));
    }

    public function create()
    {
        return view('sslcerts::create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'p12_file'  => ['required', 'file', 'max:4096'],
            'p12_pin'   => ['required', 'string'],
        ]);

        $p12Data = file_get_contents($request->file('p12_file')->getRealPath());
        $pin     = $request->input('p12_pin');

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

        // Metadaten auslesen
        $parsed = openssl_x509_parse($certPem);

        $subjectCn  = $parsed['subject']['CN']  ?? null;
        $subjectO   = $parsed['subject']['O']   ?? null;
        $subjectOu  = $parsed['subject']['OU']  ?? null;
        $issuerCn   = $parsed['issuer']['CN']   ?? null;
        $issuerO    = $parsed['issuer']['O']    ?? null;
        $serial     = $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? null);
        $validFrom  = isset($parsed['validFrom_time_t'])  ? \Carbon\Carbon::createFromTimestamp($parsed['validFrom_time_t'])  : null;
        $validTo    = isset($parsed['validTo_time_t'])    ? \Carbon\Carbon::createFromTimestamp($parsed['validTo_time_t'])    : null;

        // SANs parsen (z.B. "DNS:example.com, DNS:www.example.com, IP Address:1.2.3.4")
        $sanRaw = $parsed['extensions']['subjectAltName'] ?? '';
        $sans   = [];
        if ($sanRaw) {
            foreach (explode(',', $sanRaw) as $entry) {
                $entry = trim($entry);
                if ($entry) $sans[] = $entry;
            }
        }

        // Fingerprints
        $sha1   = openssl_x509_fingerprint($certPem, 'sha1')   ?: null;
        $sha256 = openssl_x509_fingerprint($certPem, 'sha256') ?: null;

        SslCertificate::create([
            'name'               => $request->input('name'),
            'subject_cn'         => $subjectCn,
            'subject_o'          => $subjectO,
            'subject_ou'         => $subjectOu,
            'issuer_cn'          => $issuerCn,
            'issuer_o'           => $issuerO,
            'serial_number'      => $serial,
            'valid_from'         => $validFrom,
            'valid_to'           => $validTo,
            'san'                => $sans ?: null,
            'fingerprint_sha1'   => $sha1,
            'fingerprint_sha256' => $sha256,
            'cert_pem'           => $certPem,
            'private_key'        => $privateKey,
        ]);

        return redirect()->route('sslcerts.index')
            ->with('success', 'Zertifikat „' . $request->input('name') . '" wurde erfolgreich importiert.');
    }

    public function show(SslCertificate $cert)
    {
        return view('sslcerts::show', compact('cert'));
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

    private function safeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    }
}
