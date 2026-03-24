<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Models\User;
use App\Modules\Calendar\Services\IcsService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class IcsController extends Controller
{
    public function feed(string $token, IcsService $ics): Response
    {
        $user = User::where('ics_token', $token)->firstOrFail();

        $content = $ics->generate($user);

        return response($content, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="itcockpit-kalender.ics"',
        ]);
    }
}
