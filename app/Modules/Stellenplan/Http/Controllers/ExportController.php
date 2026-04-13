<?php

namespace App\Modules\Stellenplan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stellenplan\Services\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService,
    ) {}

    public function xlsx(Request $request): Response
    {
        $this->authorize('module.stellenplan.view');

        return $this->exportService->exportXlsx($request->user());
    }

    public function pdf(Request $request): Response
    {
        $this->authorize('module.stellenplan.view');

        return $this->exportService->exportPdf($request->user());
    }
}
