<?php

namespace App\Modules\Onboarding\Http\Controllers;

use App\Modules\Onboarding\Models\OnboardingRecord;
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
}
