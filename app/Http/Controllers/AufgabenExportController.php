<?php

namespace App\Http\Controllers;

use App\Services\AufgabenExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AufgabenExportController extends Controller
{
    public function __construct(private AufgabenExportService $exportService) {}

    public function xlsx(Request $request): Response
    {
        $this->authorize('base.aufgaben.view');

        return $this->exportService->exportXlsx($request->query(), $request->user());
    }

    public function pdf(Request $request): Response
    {
        $this->authorize('base.aufgaben.view');

        return $this->exportService->exportPdf($request->query(), $request->user());
    }
}
