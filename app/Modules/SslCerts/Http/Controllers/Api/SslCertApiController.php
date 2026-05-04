<?php

namespace App\Modules\SslCerts\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\SslCerts\Http\Resources\SslCertificateResource;
use App\Modules\SslCerts\Models\SslCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SslCertApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasModulePermission('sslcerts', 'view') || $request->user()->isSuperAdmin(), 403);

        $query = SslCertificate::with('responsibleUser')
            ->when($request->filled('expired'), fn($q) => $q->where('valid_to', '<', now()))
            ->when($request->filled('expiring_days'), fn($q) =>
                $q->whereBetween('valid_to', [now(), now()->addDays((int) $request->expiring_days)])
            )
            ->when($request->filled('search'), fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('subject_cn', 'like', '%' . $request->search . '%')
            ));

        return SslCertificateResource::collection($query->orderBy('valid_to')->paginate(100));
    }

    public function show(Request $request, SslCertificate $sslCertificate): SslCertificateResource
    {
        abort_unless($request->user()->hasModulePermission('sslcerts', 'view') || $request->user()->isSuperAdmin(), 403);

        $sslCertificate->load('responsibleUser');
        return new SslCertificateResource($sslCertificate);
    }
}
