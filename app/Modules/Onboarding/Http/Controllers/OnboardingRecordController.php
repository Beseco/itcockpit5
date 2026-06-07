<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\OnboardingRecord;
use App\Modules\Onboarding\Services\ExchangeMailboxService;
use Illuminate\Http\JsonResponse;
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

    public function retryMailbox(OnboardingRecord $record): JsonResponse
    {
        if ($record->status !== 'erfolgreich') {
            return response()->json(['ok' => false, 'message' => 'AD-Benutzer noch nicht erfolgreich angelegt.']);
        }

        $exchange = new ExchangeMailboxService();
        if (!$exchange->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Exchange nicht konfiguriert.']);
        }

        $result = $exchange->enableMailbox($record->samaccountname);

        $record->update([
            'mailbox_status'     => $result['success'] ? 'aktiviert' : 'fehler',
            'mailbox_enabled_at' => $result['success'] ? now() : null,
            'mailbox_error'      => $result['success']
                ? ($result['output'] ?: null)
                : ($result['error'] ?: $result['output']),
        ]);

        return response()->json([
            'ok'      => $result['success'],
            'message' => $result['success'] ? $result['output'] : ($result['error'] ?: $result['output']),
        ]);
    }
}
