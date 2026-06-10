<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Models\AuditLog;
use App\Modules\Onboarding\Models\OnboardingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class OnboardingRecordController extends Controller
{
    public function index()
    {
        $records = OnboardingRecord::with(['vorlage', 'createdBy'])
            ->latest()
            ->paginate(30);

        return view('onboarding::records.index', compact('records'));
    }

    public function show(OnboardingRecord $record)
    {
        $record->load(['vorlage', 'createdBy']);
        $password = session('onboarding_password');

        return view('onboarding::onboarding.show', compact('record', 'password'));
    }

    /**
     * Löscht den Onboarding-Datensatz (nur das Protokoll im IT-Cockpit –
     * der AD-Benutzer und das Heimatverzeichnis bleiben unangetastet).
     */
    public function destroy(OnboardingRecord $record): RedirectResponse
    {
        $info = ['samaccountname' => $record->samaccountname, 'upn' => $record->upn];

        $record->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'module'  => 'onboarding',
            'action'  => 'record_deleted',
            'payload' => $info,
        ]);

        return redirect()
            ->back()
            ->with('success', "Onboarding-Vorgang für {$info['samaccountname']} wurde gelöscht.");
    }
}
