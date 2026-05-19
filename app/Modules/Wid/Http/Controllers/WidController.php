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
        $settings    = WidSettings::getInstance();
        $search      = $request->get('search', '');
        $filterClass = $request->get('classification', '');
        $filterPeriod = $request->get('period', '');   // today, week, ''
        $minCvss     = $request->get('min_cvss', '');
        $sortBy      = $request->get('sort', 'published');
        $sortDir     = $request->get('dir', 'desc');

        $allowedSorts = ['published', 'published_original', 'temporal_score', 'classification', 'name'];
        $sortBy  = in_array($sortBy, $allowedSorts) ? $sortBy : 'published';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $query = WidAdvisory::query()->aboveMinClassification($settings->min_classification);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($filterClass && in_array($filterClass, WidAdvisory::CLASSIFICATIONS)) {
            $query->where('classification', $filterClass);
        }

        if ($filterPeriod === 'today') {
            $query->whereDate('published', today());
        } elseif ($filterPeriod === 'week') {
            $query->where('published', '>=', now()->startOfWeek());
        }

        if ($minCvss !== '' && is_numeric($minCvss)) {
            $query->where('temporal_score', '>=', (float) $minCvss);
        }

        if ($sortBy === 'classification') {
            // Schwere absteigend = kritisch zuerst
            $classOrder = $sortDir === 'desc'
                ? "FIELD(classification, 'kritisch','hoch','mittel','niedrig','keine')"
                : "FIELD(classification, 'keine','niedrig','mittel','hoch','kritisch')";
            $query->orderByRaw($classOrder);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $advisories = $query->paginate(50)->withQueryString();

        return view('wid::index', compact(
            'advisories', 'settings', 'search', 'filterClass',
            'filterPeriod', 'minCvss', 'sortBy', 'sortDir'
        ));
    }
}
