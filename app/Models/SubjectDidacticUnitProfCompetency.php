<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель для pivot таблицы subject_didactic_unit_prof_competency
 */
class SubjectDidacticUnitProfCompetency extends Model
{
    protected $table = 'subject_didactic_unit_prof_competency';
    
    public $timestamps = true;
    
    protected $fillable = [
        'subject_type',
        'subject_id',
        'didactic_unit_id',
        'prof_competency_id'
    ];

    /**
     * Получить предмет (МДК или ОП) через полиморфную связь
     */
    public function subject()
    {
        if ($this->subject_type === 'modul') {
            return $this->belongsTo(ModulSubject::class, 'subject_id');
        }
        return $this->belongsTo(OpSubject::class, 'subject_id');
    }

    /**
     * Получить дидактическую единицу
     */
    public function didacticUnit()
    {
        return $this->belongsTo(DidacticUnit::class, 'didactic_unit_id');
    }

    /**
     * Получить профессиональную компетенцию
     */
    public function profCompetency()
    {
        return $this->belongsTo(ProfCompetency::class, 'prof_competency_id');
    }
}

