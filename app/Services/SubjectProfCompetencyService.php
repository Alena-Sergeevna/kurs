<?php

namespace App\Services;

use App\Models\ProfCompetency;
use App\Models\SubjectProfCompetency;

/**
 * Сервис для работы со связями предметов и профессиональных компетенций
 */
class SubjectProfCompetencyService
{
    /**
     * Синхронизировать связи МДК с компетенцией
     * 
     * @param int $competencyId ID компетенции
     * @param array $subjectIds Массив ID предметов
     * @param bool $approved Статус утверждения
     * @return ProfCompetency
     */
    public function syncModulSubjects(int $competencyId, array $subjectIds, bool $approved = false): ProfCompetency
    {
        $competency = ProfCompetency::findOrFail($competencyId);
        
        // Удаляем старые связи для этого типа
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'modul')
            ->delete();
        
        // Добавляем новые связи
        if (!empty($subjectIds)) {
            $insertData = array_map(function($subjectId) use ($competencyId, $approved) {
                return [
                    'subject_type' => 'modul',
                    'subject_id' => $subjectId,
                    'prof_competency_id' => $competencyId,
                    'approved' => $approved,
                ];
            }, $subjectIds);
            
            SubjectProfCompetency::insert($insertData);
        }
        
        return $competency->load(['modulSubjects', 'opSubjects']);
    }

    /**
     * Синхронизировать связи ОП с компетенцией
     * 
     * @param int $competencyId ID компетенции
     * @param array $subjectIds Массив ID предметов
     * @param bool $approved Статус утверждения
     * @return ProfCompetency
     */
    public function syncOpSubjects(int $competencyId, array $subjectIds, bool $approved = false): ProfCompetency
    {
        $competency = ProfCompetency::findOrFail($competencyId);
        
        // Удаляем старые связи для этого типа
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'op')
            ->delete();
        
        // Добавляем новые связи
        if (!empty($subjectIds)) {
            $insertData = array_map(function($subjectId) use ($competencyId, $approved) {
                return [
                    'subject_type' => 'op',
                    'subject_id' => $subjectId,
                    'prof_competency_id' => $competencyId,
                    'approved' => $approved,
                ];
            }, $subjectIds);
            
            SubjectProfCompetency::insert($insertData);
        }
        
        return $competency->load(['modulSubjects', 'opSubjects']);
    }

    /**
     * Утвердить все связи компетенции
     * 
     * @param int $competencyId ID компетенции
     * @return ProfCompetency
     */
    public function approveAllRelations(int $competencyId): ProfCompetency
    {
        $competency = ProfCompetency::findOrFail($competencyId);
        
        // Утверждаем все связи МДК
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'modul')
            ->update(['approved' => true]);
        
        // Утверждаем все связи ОП
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'op')
            ->update(['approved' => true]);
        
        return $competency->load(['modulSubjects', 'opSubjects']);
    }

    /**
     * Разутвердить все связи компетенции
     * 
     * @param int $competencyId ID компетенции
     * @return ProfCompetency
     */
    public function unapproveAllRelations(int $competencyId): ProfCompetency
    {
        $competency = ProfCompetency::findOrFail($competencyId);
        
        // Разутверждаем все связи МДК
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'modul')
            ->update(['approved' => false]);
        
        // Разутверждаем все связи ОП
        SubjectProfCompetency::where('prof_competency_id', $competencyId)
            ->where('subject_type', 'op')
            ->update(['approved' => false]);
        
        return $competency->load(['modulSubjects', 'opSubjects']);
    }
}

