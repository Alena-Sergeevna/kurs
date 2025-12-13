<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectProfCompetencyDraft extends Model
{
    protected $fillable = [
        'original_subject_type',
        'original_subject_id',
        'original_prof_competency_id',
        'new_subject_type',
        'new_subject_id',
        'new_prof_competency_id',
        'action',
        'comment',
        'created_by',
        'draft_batch_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
