<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\CallerLookupService;
use Illuminate\Http\Request;

/**
 * CTI-Screen-Pop: Teams öffnet bei eingehenden Anrufen /lookup?phone={phone}.
 * Zeigt dem Mitarbeiter sofort, wer anruft (Dienstleister, Ansprechpartner
 * oder interner AD-Kollege).
 */
class CtiLookupController extends Controller
{
    public function __construct(
        private readonly CallerLookupService $lookup,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        // Teams sendet E.164 mit '+'. Ein nicht URL-enkodiertes '+' wird vom
        // Webserver zu einem führenden Leerzeichen dekodiert → wiederherstellen.
        $phone = (string) $request->query('phone', '');
        if (preg_match('/^\s/', $phone)) {
            $phone = '+' . ltrim($phone);
        }

        $result = $this->lookup->lookup($phone);

        $matchCount = count($result['matches']);
        $matchType  = $matchCount > 0 ? $result['matches'][0]['match_type'] : 'none';

        $this->auditLogger->log('CTI', 'Lookup', [
            'phone'      => $phone,
            'e164'       => $result['e164'],
            'matched'    => $matchCount > 0,
            'count'      => $matchCount,
            'match_type' => $matchType,
        ]);

        return view('cti.lookup', ['result' => $result]);
    }
}
