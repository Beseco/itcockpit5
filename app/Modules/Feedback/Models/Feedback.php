<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'q1_overall',
        'q2_processing_time',
        'q3_communication',
        'q4_simplicity',
        'q5_competence',
        'comment',
        'ip_hash',
    ];

    protected $casts = [
        'q1_overall'         => 'integer',
        'q2_processing_time' => 'integer',
        'q3_communication'   => 'integer',
        'q4_simplicity'      => 'integer',
        'q5_competence'      => 'integer',
    ];

    public function averageScore(): float
    {
        return round(
            ($this->q1_overall + $this->q2_processing_time + $this->q3_communication
                + $this->q4_simplicity + $this->q5_competence) / 5,
            2
        );
    }

    public static function questionLabels(): array
    {
        return [
            'q1_overall'         => 'Gesamtzufriedenheit',
            'q2_processing_time' => 'Bearbeitungszeit',
            'q3_communication'   => 'Kommunikation',
            'q4_simplicity'      => 'Unkompliziertheit',
            'q5_competence'      => 'Fachliche Kompetenz',
        ];
    }
}
