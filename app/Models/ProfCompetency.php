<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProfCompetency extends Model
{
    protected $table = 'prof_competencies';
    
    public $incrementing = false;
    protected $keyType = 'int';
    
    protected $fillable = ['id', 'name', 'id_module'];

    public function modul(): BelongsTo
    {
        return $this->belongsTo(Modul::class, 'id_module');
    }

    public function modulSubjects(): BelongsToMany
    {
        return $this->belongsToMany(ModulSubject::class, 'subject_prof_competency', 'prof_competency_id', 'subject_id')
            ->where('subject_prof_competency.subject_type', 'modul')
            ->withPivot('approved', 'subject_type')
            ->withTimestamps();
    }

    public function opSubjects(): BelongsToMany
    {
        return $this->belongsToMany(OpSubject::class, 'subject_prof_competency', 'prof_competency_id', 'subject_id')
            ->where('subject_prof_competency.subject_type', 'op')
            ->withPivot('approved', 'subject_type')
            ->withTimestamps();
    }
}
