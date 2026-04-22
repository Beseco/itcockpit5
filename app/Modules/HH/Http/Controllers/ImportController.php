<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Services\BudgetYearService;
use App\Modules\HH\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private BudgetYearService $budgetYearService,
    ) {}

    /**
     * GET /hh/import
     * Show the CSV import form.
     */
    public function index(Request $request): View
    {
        $budgetYears = BudgetYear::orderByDesc('year')->get();

        return view('hh::import.index', [
            'budgetYears' => $budgetYears,
        ]);
    }

    /**
     * POST /hh/import
     * Process the uploaded CSV file.
     */
    public function store(Request $request): View|RedirectResponse
    {
        $request->validate([
            'csv_file'       => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'budget_year_id' => ['required', 'exists:hh_budget_years,id'],
        ], [
            'csv_file.required'       => 'Bitte wählen Sie eine CSV-Datei aus.',
            'csv_file.mimes'          => 'Nur CSV-Dateien (.csv, .txt) sind erlaubt.',
            'csv_file.max'            => 'Die Datei darf maximal 5 MB groß sein.',
            'budget_year_id.required' => 'Bitte wählen Sie ein Haushaltsjahr aus.',
            'budget_year_id.exists'   => 'Das gewählte Haushaltsjahr existiert nicht.',
        ]);

        $budgetYear = BudgetYear::findOrFail($request->budget_year_id);

        if ($budgetYear->status === 'approved') {
            return back()->withErrors(['budget_year_id' => 'Ein genehmigtes Haushaltsjahr kann nicht mehr importiert werden.']);
        }

        $result = $this->importService->importCsv(
            $request->file('csv_file'),
            $budgetYear,
            $request->user()
        );

        $budgetYears = BudgetYear::orderByDesc('year')->get();

        return view('hh::import.index', [
            'budgetYears'      => $budgetYears,
            'result'           => $result,
            'importedYear'     => $budgetYear->year,
            'selectedYearId'   => $budgetYear->id,
        ]);
    }
}
