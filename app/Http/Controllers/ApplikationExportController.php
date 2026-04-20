<?php

namespace App\Http\Controllers;

use App\Services\ApplikationExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplikationExportController extends Controller
{
    public function __construct(private ApplikationExportService $exportService) {}

    public function xlsx(Request $request): Response
    {
        $this->authorize('applikationen.view');

        return $this->exportService->exportXlsx($request->query());
    }

    public function pdf(Request $request): Response
    {
        $this->authorize('applikationen.view');

        return $this->exportService->exportPdf($request->query());
    }
}
