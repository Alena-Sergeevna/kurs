<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovedVersion extends Model
{
    protected $fillable = [
        'version_number',
        'approved_by',
        'approved_at',
        'changes_summary',
        'snapshot_before',
        'snapshot_after',
        'draft_batch_id',
    ];

    protected $casts = [
        'snapshot_before' => 'array',
        'snapshot_after' => 'array',
        'changes_summary' => 'array', // Может быть JSON строкой или массивом
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
