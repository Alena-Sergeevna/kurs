<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DidacticUnitDraft extends Model
{
    protected $fillable = [
        'draft_batch_id',
        'subject_type',
        'subject_id',
        'prof_competency_id',
        'original_didactic_unit_id',
        'original_didactic_unit_ids',
        'new_didactic_unit_id',
        'new_didactic_unit_name',
        'new_didactic_unit_type',
        'action',
        'target_subject_type',
        'target_subject_id',
        'target_prof_competency_id',
        'comment',
        'created_by',
    ];

    protected $casts = [
        'original_didactic_unit_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
