<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель для pivot таблицы subject_prof_competency
 */
class SubjectProfCompetency extends Model
{
    protected $table = 'subject_prof_competency';
    
    public $timestamps = true;
    
    protected $fillable = [
        'subject_type',
        'subject_id',
        'prof_competency_id',
        'approved',
        'approved_version_id'
    ];

    protected $casts = [
        'approved' => 'boolean',
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
     * Получить профессиональную компетенцию
     */
    public function profCompetency()
    {
        return $this->belongsTo(ProfCompetency::class, 'prof_competency_id');
    }
}

