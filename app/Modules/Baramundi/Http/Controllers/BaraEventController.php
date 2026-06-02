<?php

namespace App\Modules\Baramundi\Http\Controllers;

use App\Modules\Baramundi\Models\BaraEvent;
use App\Modules\Baramundi\Models\WatchedPackage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BaraEventController extends Controller
{
    public function index(Request $request)
    {
        $filterPackage   = $request->input('package_id', '');
        $filterEventType = $request->input('event_type', '');

        $query = BaraEvent::with('package')->orderByDesc('created_at');

        if ($filterPackage) {
            $query->where('package_id', $filterPackage);
        }

        if ($filterEventType) {
            $query->where('event_type', $filterEventType);
        }

        $events     = $query->paginate(30)->withQueryString();
        $packages   = WatchedPackage::orderBy('name')->pluck('name', 'id');
        $eventTypes = BaraEvent::TYPE_LABELS;

        return view('baramundi::events', compact('events', 'packages', 'eventTypes', 'filterPackage', 'filterEventType'));
    }
}
