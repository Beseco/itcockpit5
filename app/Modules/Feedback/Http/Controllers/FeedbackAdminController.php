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

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort   = $request->get('sort', 'created_at');
        $dir    = $request->get('dir', 'desc');

        $allowed = ['created_at', 'q1_overall', 'q2_processing_time', 'q3_communication', 'q4_simplicity', 'q5_competence'];
        if (!in_array($sort, $allowed)) {
            $sort = 'created_at';
        }

        $query = Feedback::query();

        if ($search) {
            $query->where('comment', 'LIKE', '%' . $search . '%');
        }

        $feedbacks = $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        $questionLabels = Feedback::questionLabels();

        return view('feedback::admin.index', compact('feedbacks', 'questionLabels', 'search', 'sort', 'dir'));
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return back()->with('success', 'Bewertung wurde gelöscht.');
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
