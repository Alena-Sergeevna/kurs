<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidacticUnit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DidacticUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $units = DidacticUnit::with(['modulSubjects', 'opSubjects'])->get();
        return response()->json($units);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:know,be_able,have_practical_experience',
            'name' => 'required|string|max:255',
        ]);

        $unit = DidacticUnit::create($validated);
        return response()->json($unit, 201);
    }

    public function show(string $id): JsonResponse
    {
        $unit = DidacticUnit::with(['modulSubjects', 'opSubjects'])->findOrFail($id);
        return response()->json($unit);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:know,be_able,have_practical_experience',
            'name' => 'sometimes|string|max:255',
        ]);

        $unit = DidacticUnit::findOrFail($id);
        $unit->update($validated);
        return response()->json($unit);
    }

    public function destroy(string $id): JsonResponse
    {
        $unit = DidacticUnit::findOrFail($id);
        $unit->delete();
        return response()->json(['message' => 'Дидактическая единица успешно удалена'], 200);
    }

    /**
     * Получить данные для таблицы связей ДЕ
     */
    public function table(): JsonResponse
    {
        $data = \Illuminate\Support\Facades\DB::table('subject_didactic_unit_prof_competency')
            ->join('prof_competencies', 'prof_competencies.id', '=', 'subject_didactic_unit_prof_competency.prof_competency_id')
            ->join('moduls', 'moduls.id', '=', 'prof_competencies.id_module')
            ->join('didactic_units', 'didactic_units.id', '=', 'subject_didactic_unit_prof_competency.didactic_unit_id')
            ->leftJoin('modulsubjects', function($join) {
                $join->on('modulsubjects.id', '=', 'subject_didactic_unit_prof_competency.subject_id')
                    ->where('subject_didactic_unit_prof_competency.subject_type', '=', 'modul');
            })
            ->leftJoin('op_subjects', function($join) {
                $join->on('op_subjects.id', '=', 'subject_didactic_unit_prof_competency.subject_id')
                    ->where('subject_didactic_unit_prof_competency.subject_type', '=', 'op');
            })
            ->select(
                'moduls.id as moduleId',
                'moduls.name as moduleName',
                'prof_competencies.id as competencyId',
                'prof_competencies.name as competencyName',
                'subject_didactic_unit_prof_competency.subject_type as subjectType',
                'subject_didactic_unit_prof_competency.subject_id as subjectId',
                \DB::raw('COALESCE(modulsubjects.name, op_subjects.name) as subjectName'),
                'didactic_units.id as unitId',
                'didactic_units.name as unitName',
                'didactic_units.type as unitType'
            )
            ->orderBy('moduls.id')
            ->orderBy('prof_competencies.id')
            ->orderBy('subject_didactic_unit_prof_competency.subject_type')
            ->orderBy('subject_didactic_unit_prof_competency.subject_id')
            ->get();

        return response()->json($data);
    }

    /**
     * Анализ дублирования ДЕ
     */
    public function duplicates(): JsonResponse
    {
        // Получаем все ДЕ с их связями
        $units = DidacticUnit::all();
        
        // Группируем по типу и точному тексту (без нормализации)
        $textMap = [];
        foreach ($units as $unit) {
            $key = $unit->type . '_' . trim($unit->name);
            
            if (!isset($textMap[$key])) {
                $textMap[$key] = [
                    'text' => $unit->name,
                    'type' => $unit->type,
                    'ids' => []
                ];
            }
            
            // Добавляем ID только если его еще нет
            if (!in_array($unit->id, $textMap[$key]['ids'])) {
                $textMap[$key]['ids'][] = $unit->id;
            }
        }
        
        // Фильтруем только дубликаты (где больше одного ID)
        $duplicates = [];
        foreach ($textMap as $key => $data) {
            if (count($data['ids']) > 1) {
                // Получаем места использования для всех ID этого дубликата
                $locationsMap = [];
                
                foreach ($data['ids'] as $unitId) {
                    $rows = \DB::table('subject_didactic_unit_prof_competency')
                        ->join('prof_competencies', 'prof_competencies.id', '=', 'subject_didactic_unit_prof_competency.prof_competency_id')
                        ->join('moduls', 'moduls.id', '=', 'prof_competencies.id_module')
                        ->leftJoin('modulsubjects', function($join) {
                            $join->on('modulsubjects.id', '=', 'subject_didactic_unit_prof_competency.subject_id')
                                ->where('subject_didactic_unit_prof_competency.subject_type', '=', 'modul');
                        })
                        ->leftJoin('op_subjects', function($join) {
                            $join->on('op_subjects.id', '=', 'subject_didactic_unit_prof_competency.subject_id')
                                ->where('subject_didactic_unit_prof_competency.subject_type', '=', 'op');
                        })
                        ->where('subject_didactic_unit_prof_competency.didactic_unit_id', $unitId)
                        ->select(
                            'moduls.id as moduleId',
                            'moduls.name as moduleName',
                            'prof_competencies.id as competencyId',
                            'prof_competencies.name as competencyName',
                            'subject_didactic_unit_prof_competency.subject_type as subjectType',
                            'subject_didactic_unit_prof_competency.subject_id as subjectId',
                            \DB::raw('COALESCE(modulsubjects.name, op_subjects.name) as subjectName')
                        )
                        ->get();
                    
                    foreach ($rows as $row) {
                        // Ключ: модуль + тип предмета + ID предмета (объединяем разные ПК для одного предмета)
                        $locationKey = $row->moduleId . '_' . $row->subjectType . '_' . $row->subjectId;
                        
                        if (!isset($locationsMap[$locationKey])) {
                            $locationsMap[$locationKey] = [
                                'moduleId' => $row->moduleId,
                                'moduleName' => $row->moduleName,
                                'subjectType' => $row->subjectType,
                                'subjectId' => $row->subjectId,
                                'subjectName' => $row->subjectName,
                                'competencies' => []
                            ];
                        }
                        
                        // Добавляем ПК, если его еще нет
                        $compExists = false;
                        foreach ($locationsMap[$locationKey]['competencies'] as $comp) {
                            if ($comp['id'] == $row->competencyId) {
                                $compExists = true;
                                break;
                            }
                        }
                        
                        if (!$compExists) {
                            $locationsMap[$locationKey]['competencies'][] = [
                                'id' => $row->competencyId,
                                'name' => $row->competencyName
                            ];
                        }
                    }
                }
                
                $duplicates[] = [
                    'text' => $data['text'],
                    'type' => $data['type'],
                    'ids' => $data['ids'],
                    'duplicatesCount' => count($data['ids']), // Считаем все ДЕ с одинаковым текстом
                    'locations' => array_values($locationsMap)
                ];
            }
        }
        
        // Сортируем по количеству дубликатов
        usort($duplicates, function($a, $b) {
            if ($b['duplicatesCount'] !== $a['duplicatesCount']) {
                return $b['duplicatesCount'] - $a['duplicatesCount'];
            }
            return count($b['locations']) - count($a['locations']);
        });
        
        // Находим неиспользуемые ДЕ (нет связей в subject_didactic_unit_prof_competency)
        $usedUnitIds = \DB::table('subject_didactic_unit_prof_competency')
            ->distinct()
            ->pluck('didactic_unit_id')
            ->toArray();
        
        $unusedUnits = DidacticUnit::whereNotIn('id', $usedUnitIds)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'type' => $unit->type
                ];
            })
            ->toArray();
        
        // Статистика
        $totalUnits = $units->count();
        $uniqueTexts = count($textMap);
        // Считаем все ДЕ, которые являются дубликатами (все ДЕ с одинаковым текстом)
        $totalDuplicates = array_sum(array_column($duplicates, 'duplicatesCount'));
        
        return response()->json([
            'statistics' => [
                'totalUnits' => $totalUnits,
                'uniqueTexts' => $uniqueTexts,
                'duplicatesCount' => $totalDuplicates,
                'unusedCount' => count($unusedUnits)
            ],
            'duplicates' => $duplicates,
            'unusedUnits' => $unusedUnits
        ]);
    }
}
