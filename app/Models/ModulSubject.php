<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ModulSubject extends Model
{
    protected $table = 'modulsubjects';
    
    public $incrementing = false;
    protected $keyType = 'int';
    
    protected $fillable = ['id', 'name', 'id_module'];

    public function modul(): BelongsTo
    {
        return $this->belongsTo(Modul::class, 'id_module');
    }

    public function profCompetencies(): BelongsToMany
    {
        return $this->belongsToMany(ProfCompetency::class, 'subject_prof_competency', 'subject_id', 'prof_competency_id')
            ->where('subject_prof_competency.subject_type', 'modul')
            ->withPivot('approved', 'subject_type')
            ->withTimestamps();
    }

    public function didacticUnits(): BelongsToMany
    {
        return $this->belongsToMany(DidacticUnit::class, 'subject_didactic_unit', 'subject_id', 'didactic_unit_id')
            ->where('subject_didactic_unit.subject_type', 'modul')
            ->withPivot('subject_type')
            ->withTimestamps();
    }

    /**
     * Получить дидактические единицы для конкретной профессиональной компетенции
     */
    public function didacticUnitsByCompetency(int $competencyId)
    {
        return $this->belongsToMany(DidacticUnit::class, 'subject_didactic_unit_prof_competency', 'subject_id', 'didactic_unit_id')
            ->wherePivot('subject_type', 'modul')
            ->wherePivot('prof_competency_id', $competencyId)
            ->withPivot('subject_type', 'prof_competency_id')
            ->withTimestamps();
    }
}
