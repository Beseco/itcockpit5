<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Services\SchulenExportService;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(private SchulenExportService $exportService) {}

    public function download(string $type, string $format): Response
    {
        return match ($type) {
            'matrix'           => $this->exportService->exportMatrix($format),
            'dienstleistungen' => $this->exportService->exportDienstleistungen($format),
            'schulen-liste'    => $this->exportService->exportSchulenListe($format),
            default            => abort(404),
        };
    }
}
