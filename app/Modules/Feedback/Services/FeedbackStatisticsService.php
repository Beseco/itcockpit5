<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Feedback\Models\Feedback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FeedbackStatisticsService
{
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Feedback::query();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            return [
                'total'   => 0,
                'average' => 0,
                'by_question' => array_fill_keys(array_keys(Feedback::questionLabels()), 0),
            ];
        }

        $averages = (clone $query)->selectRaw(
            'ROUND(AVG(q1_overall), 2)         AS q1_overall,
             ROUND(AVG(q2_processing_time), 2) AS q2_processing_time,
             ROUND(AVG(q3_communication), 2)   AS q3_communication,
             ROUND(AVG(q4_simplicity), 2)      AS q4_simplicity,
             ROUND(AVG(q5_competence), 2)      AS q5_competence'
        )->first();

        $byQuestion = [
            'q1_overall'         => (float) $averages->q1_overall,
            'q2_processing_time' => (float) $averages->q2_processing_time,
            'q3_communication'   => (float) $averages->q3_communication,
            'q4_simplicity'      => (float) $averages->q4_simplicity,
            'q5_competence'      => (float) $averages->q5_competence,
        ];

        $overallAvg = round(array_sum($byQuestion) / count($byQuestion), 2);

        return [
            'total'       => $total,
            'average'     => $overallAvg,
            'by_question' => $byQuestion,
        ];
    }

    public function trendData(string $period = '30days'): array
    {
        $from = match ($period) {
            '7days'  => now()->subDays(7)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            '90days' => now()->subDays(90)->startOfDay(),
            default  => null,
        };

        $query = Feedback::query();
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        $rows = $query
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS count, ROUND(AVG((q1_overall + q2_processing_time + q3_communication + q4_simplicity + q5_competence) / 5), 2) AS avg_score')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'labels'     => $rows->pluck('day')->toArray(),
            'counts'     => $rows->pluck('count')->map(fn ($v) => (int) $v)->toArray(),
            'avg_scores' => $rows->pluck('avg_score')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    public function commentsList(?string $search = null, int $perPage = 25)
    {
        $query = Feedback::whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where('comment', 'LIKE', '%' . $search . '%');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
