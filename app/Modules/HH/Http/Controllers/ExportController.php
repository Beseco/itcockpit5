<?php

namespace App\Modules\HH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService,
    ) {}

    /**
     * GET /hh/budget-years/{budgetYear}/export/excel
     *
     * Download budget positions as Excel (XLSX) or CSV fallback.
     *
     * Requirements: 11.1
     */
    public function excel(Request $request, BudgetYear $budgetYear): StreamedResponse
    {
        return $this->exportService->exportExcel($budgetYear, $request->user());
    }

    /**
     * GET /hh/budget-years/{budgetYear}/export/pdf
     *
     * Download budget positions as PDF or print-friendly HTML fallback.
     *
     * Requirements: 11.1
     */
    public function pdf(Request $request, BudgetYear $budgetYear): Response
    {
        return $this->exportService->exportPdf($budgetYear, $request->user());
    }
}
