<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class FeedbackController extends Controller
{
    public function show(Request $request)
    {
        if ($request->cookie('feedback_submitted')) {
            return view('feedback::already-submitted');
        }

        return view('feedback::form');
    }

    public function store(Request $request)
    {
        // Honeypot: silently discard bot submissions
        if ($request->filled('website')) {
            return redirect()->route('feedback.thank-you')
                ->withCookie(Cookie::make('feedback_submitted', '1', 60 * 24, '/', null, false, true));
        }

        $validated = $request->validate([
            'q1_overall'         => 'required|integer|min:1|max:5',
            'q2_processing_time' => 'required|integer|min:1|max:5',
            'q3_communication'   => 'required|integer|min:1|max:5',
            'q4_simplicity'      => 'required|integer|min:1|max:5',
            'q5_competence'      => 'required|integer|min:1|max:5',
            'comment'            => 'nullable|string|max:2000',
        ]);

        $ip = $request->ip();
        $validated['ip_hash'] = $ip ? hash('sha256', $ip) : null;

        Feedback::create($validated);

        return redirect()->route('feedback.thank-you')
            ->withCookie(Cookie::make('feedback_submitted', '1', 60 * 24, '/', null, false, true));
    }

    public function thankYou()
    {
        return view('feedback::thank-you');
    }
}
