<?php

namespace App\Modules\SslCerts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SslCerts\Models\SslCertsSettings;
use Illuminate\Http\Request;

class SslCertsSettingsController extends Controller
{
    public function index()
    {
        $settings = SslCertsSettings::getSingleton();
        return view('sslcerts::settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'notification_email'    => ['nullable', 'email', 'max:255'],
            'notifications_enabled' => ['nullable'],
        ]);

        $settings = SslCertsSettings::getSingleton();
        $settings->notification_email    = $request->input('notification_email');
        $settings->notifications_enabled = $request->boolean('notifications_enabled');
        $settings->save();

        return redirect()->route('sslcerts.settings')
            ->with('success', 'Einstellungen wurden gespeichert.');
    }
}
