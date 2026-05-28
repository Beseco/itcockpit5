<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Models\Feedback;
use App\Modules\Feedback\Services\FeedbackStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FeedbackAdminController extends Controller
{
    public function __construct(private FeedbackStatisticsService $stats) {}

    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30days');

        [$from, $to] = $this->periodRange($period);

        $summary   = $this->stats->summary($from, $to);
        $trendData = $this->stats->trendData($period);

        $questionLabels = Feedback::questionLabels();

        return view('feedback::admin.dashboard', compact(
            'summary', 'trendData', 'questionLabels', 'period'
        ));
    }

    public function comments(Request $request)
    {
        $search   = $request->get('search', '');
        $comments = $this->stats->commentsList($search ?: null);

        return view('feedback::admin.comments', compact('comments', 'search'));
    }

    private function periodRange(string $period): array
    {
        return match ($period) {
            'today'  => [now()->startOfDay(), now()->endOfDay()],
            '7days'  => [now()->subDays(7)->startOfDay(), null],
            '30days' => [now()->subDays(30)->startOfDay(), null],
            '90days' => [now()->subDays(90)->startOfDay(), null],
            default  => [null, null],
        };
    }
}
