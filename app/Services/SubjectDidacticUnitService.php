<?php

namespace App\Services;

use App\Models\SubjectDidacticUnitProfCompetency;

/**
 * Сервис для работы со связями предметов и дидактических единиц
 */
class SubjectDidacticUnitService
{
    /**
     * Синхронизировать дидактические единицы для предмета по профессиональной компетенции
     * 
     * @param string $subjectType Тип предмета ('modul' или 'op')
     * @param int $subjectId ID предмета
     * @param int $profCompetencyId ID профессиональной компетенции
     * @param array $didacticUnitIds Массив ID дидактических единиц
     * @return void
     */
    public function syncDidacticUnits(
        string $subjectType,
        int $subjectId,
        int $profCompetencyId,
        array $didacticUnitIds,
        bool $approve = false
    ): void {
        // Удаляем все старые связи для этой пары предмет-ПК
        SubjectDidacticUnitProfCompetency::where('subject_id', $subjectId)
            ->where('subject_type', $subjectType)
            ->where('prof_competency_id', $profCompetencyId)
            ->delete();
        
        // Добавляем новые связи
        if (!empty($didacticUnitIds)) {
            $insertData = array_map(function($unitId) use ($subjectType, $subjectId, $profCompetencyId, $approve) {
                return [
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'didactic_unit_id' => $unitId,
                    'prof_competency_id' => $profCompetencyId,
                    'approved' => $approve,
                ];
            }, $didacticUnitIds);
            
            SubjectDidacticUnitProfCompetency::insert($insertData);
        }
    }
    
    /**
     * Утвердить все ДЕ для связи предмет-ПК
     */
    public function approveDidacticUnits(
        string $subjectType,
        int $subjectId,
        int $profCompetencyId
    ): void {
        SubjectDidacticUnitProfCompetency::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('prof_competency_id', $profCompetencyId)
            ->update(['approved' => true]);
    }
    
    /**
     * Разутвердить все ДЕ для связи предмет-ПК
     */
    public function unapproveDidacticUnits(
        string $subjectType,
        int $subjectId,
        int $profCompetencyId
    ): void {
        SubjectDidacticUnitProfCompetency::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('prof_competency_id', $profCompetencyId)
            ->update(['approved' => false]);
    }

    /**
     * Получить дидактические единицы для предмета по компетенции
     * 
     * @param string $subjectType Тип предмета ('modul' или 'op')
     * @param int $subjectId ID предмета
     * @param int $profCompetencyId ID профессиональной компетенции
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDidacticUnitsByCompetency(
        string $subjectType,
        int $subjectId,
        int $profCompetencyId
    ) {
        return SubjectDidacticUnitProfCompetency::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('prof_competency_id', $profCompetencyId)
            ->with('didacticUnit:id,name,type')
            ->get()
            ->map(function ($item) {
                return $item->didacticUnit;
            });
    }

    /**
     * Массовая загрузка дидактических единиц для списка предметов и компетенций
     * 
     * @param array $requests Массив запросов: [{subject_type, subject_id, competency_id}]
     * @return array Результат с ключами вида "subject_type_subject_id_competency_id"
     */
    public function bulkLoadBySubjects(array $requests): array
    {
        $result = [];
        
        // Группируем запросы по типам для оптимизации
        $modulQueries = [];
        $opQueries = [];
        
        foreach ($requests as $request) {
            $key = "{$request['subject_type']}_{$request['subject_id']}_{$request['competency_id']}";
            
            if ($request['subject_type'] === 'modul') {
                $modulQueries[$key] = $request;
            } else {
                $opQueries[$key] = $request;
            }
        }

        // Загружаем ДЕ для МДК
        if (!empty($modulQueries)) {
            $modulSubjectIds = array_unique(array_column($modulQueries, 'subject_id'));
            $modulCompetencyIds = array_unique(array_column($modulQueries, 'competency_id'));
            
            $modulUnits = SubjectDidacticUnitProfCompetency::where('subject_type', 'modul')
                ->whereIn('subject_id', $modulSubjectIds)
                ->whereIn('prof_competency_id', $modulCompetencyIds)
                ->with('didacticUnit:id,name,type')
                ->get()
                ->groupBy(function ($item) {
                    return "modul_{$item->subject_id}_{$item->prof_competency_id}";
                })
                ->map(function ($group) {
                    return $group->map(function ($item) {
                        return [
                            'id' => $item->didacticUnit->id,
                            'name' => $item->didacticUnit->name,
                            'type' => $item->didacticUnit->type,
                            'approved' => $item->approved,
                        ];
                    });
                });

            foreach ($modulQueries as $key => $subject) {
                $groupKey = "modul_{$subject['subject_id']}_{$subject['competency_id']}";
                $result[$key] = $modulUnits->get($groupKey, collect())->values()->toArray();
            }
        }

        // Загружаем ДЕ для ОП
        if (!empty($opQueries)) {
            $opSubjectIds = array_unique(array_column($opQueries, 'subject_id'));
            $opCompetencyIds = array_unique(array_column($opQueries, 'competency_id'));
            
            $opUnits = SubjectDidacticUnitProfCompetency::where('subject_type', 'op')
                ->whereIn('subject_id', $opSubjectIds)
                ->whereIn('prof_competency_id', $opCompetencyIds)
                ->with('didacticUnit:id,name,type')
                ->get()
                ->groupBy(function ($item) {
                    return "op_{$item->subject_id}_{$item->prof_competency_id}";
                })
                ->map(function ($group) {
                    return $group->map(function ($item) {
                        return [
                            'id' => $item->didacticUnit->id,
                            'name' => $item->didacticUnit->name,
                            'type' => $item->didacticUnit->type,
                            'approved' => $item->approved,
                        ];
                    });
                });

            foreach ($opQueries as $key => $subject) {
                $groupKey = "op_{$subject['subject_id']}_{$subject['competency_id']}";
                $result[$key] = $opUnits->get($groupKey, collect())->values()->toArray();
            }
        }

        return $result;
    }
}

