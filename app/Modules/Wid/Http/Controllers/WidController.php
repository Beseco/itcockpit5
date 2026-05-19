<?php

namespace App\Modules\Wid\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wid\Models\WidAdvisory;
use App\Modules\Wid\Models\WidSettings;
use Illuminate\Http\Request;

class WidController extends Controller
{
    public function index(Request $request)
    {
        $settings       = WidSettings::getInstance();
        $minClass       = $settings->min_classification;
        $search         = $request->get('search', '');
        $filterClass    = $request->get('classification', '');

        $query = WidAdvisory::query()
            ->aboveMinClassification($minClass)
            ->orderByRaw("FIELD(classification, 'kritisch','hoch','mittel','niedrig','keine')")
            ->orderByDesc('published');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($filterClass && in_array($filterClass, WidAdvisory::CLASSIFICATIONS)) {
            $query->where('classification', $filterClass);
        }

        $advisories = $query->paginate(50)->withQueryString();

        return view('wid::index', compact('advisories', 'settings', 'search', 'filterClass'));
    }
}
