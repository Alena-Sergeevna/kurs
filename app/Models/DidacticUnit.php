<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DidacticUnit extends Model
{
    protected $table = 'didactic_units';
    
    protected $fillable = ['type', 'name'];

    public function modulSubjects(): BelongsToMany
    {
        return $this->belongsToMany(ModulSubject::class, 'subject_didactic_unit', 'didactic_unit_id', 'subject_id')
            ->where('subject_didactic_unit.subject_type', 'modul')
            ->withPivot('subject_type')
            ->withTimestamps();
    }

    public function opSubjects(): BelongsToMany
    {
        return $this->belongsToMany(OpSubject::class, 'subject_didactic_unit', 'didactic_unit_id', 'subject_id')
            ->where('subject_didactic_unit.subject_type', 'op')
            ->withPivot('subject_type')
            ->withTimestamps();
    }
}
