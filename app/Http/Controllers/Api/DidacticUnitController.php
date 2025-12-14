<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidacticUnit;
use App\Models\SubjectDidacticUnitProfCompetency;
use App\Services\SubjectDidacticUnitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DidacticUnitController extends Controller
{
    public function __construct(
        protected SubjectDidacticUnitService $subjectDidacticUnitService
    ) {}
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
    
    /**
     * Утвердить ДЕ для связи предмет-ПК
     */
    public function approveDidacticUnits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:modul,op',
            'subject_id' => 'required|integer',
            'prof_competency_id' => 'required|integer|exists:prof_competencies,id',
        ]);
        
        $this->subjectDidacticUnitService->approveDidacticUnits(
            $validated['subject_type'],
            $validated['subject_id'],
            $validated['prof_competency_id']
        );
        
        return response()->json([
            'message' => 'Дидактические единицы утверждены'
        ]);
    }
    
    /**
     * Разутвердить ДЕ для связи предмет-ПК
     */
    public function unapproveDidacticUnits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:modul,op',
            'subject_id' => 'required|integer',
            'prof_competency_id' => 'required|integer|exists:prof_competencies,id',
        ]);
        
        $this->subjectDidacticUnitService->unapproveDidacticUnits(
            $validated['subject_type'],
            $validated['subject_id'],
            $validated['prof_competency_id']
        );
        
        return response()->json([
            'message' => 'Дидактические единицы разутверждены'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $unit = DidacticUnit::findOrFail($id);
        
        // Удаляем все связанные черновики оценок для этой ДЕ
        \App\Models\DidacticUnitDraft::where('original_didactic_unit_id', $id)
            ->orWhere('new_didactic_unit_id', $id)
            ->orWhereJsonContains('original_didactic_unit_ids', $id)
            ->delete();
        
        $unit->delete();
        return response()->json(['message' => 'Дидактическая единица успешно удалена'], 200);
    }

    /**
     * Получить данные для таблицы связей ДЕ
     * Использует Eloquent модель вместо DB::table
     */
    public function table(): JsonResponse
    {
        $data = SubjectDidacticUnitProfCompetency::query()
            ->whereNull('approved_version_id') // Только исходные данные, не версионированные
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
            ->select([
                'moduls.id as moduleId',
                'moduls.name as moduleName',
                'prof_competencies.id as competencyId',
                'prof_competencies.name as competencyName',
                'subject_didactic_unit_prof_competency.subject_type as subjectType',
                'subject_didactic_unit_prof_competency.subject_id as subjectId',
                // Используем selectRaw для COALESCE
                DB::raw('COALESCE(modulsubjects.name, op_subjects.name) as subjectName'),
                'didactic_units.id as unitId',
                'didactic_units.name as unitName',
                'didactic_units.type as unitType'
            ])
            ->orderBy('moduls.id')
            ->orderBy('prof_competencies.id')
            ->orderBy('subject_didactic_unit_prof_competency.subject_type')
            ->orderBy('subject_didactic_unit_prof_competency.subject_id')
            ->get();

        return response()->json($data);
    }

    /**
     * Массовая загрузка дидактических единиц для списка предметов и компетенций
     * Принимает массив объектов: [{subject_type: 'modul'|'op', subject_id: int, competency_id: int}]
     * Возвращает объект с ключами вида "subject_type_subject_id_competency_id"
     */
    public function bulkLoadBySubjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*.subject_type' => 'required|in:modul,op',
            'subjects.*.subject_id' => 'required|integer',
            'subjects.*.competency_id' => 'required|integer|exists:prof_competencies,id',
        ]);

        $result = $this->subjectDidacticUnitService->bulkLoadBySubjects($validated['subjects']);

        // Заполняем пустые результаты для запросов без данных
        foreach ($validated['subjects'] as $subject) {
            $key = "{$subject['subject_type']}_{$subject['subject_id']}_{$subject['competency_id']}";
            if (!isset($result[$key])) {
                $result[$key] = [];
            }
        }

        return response()->json($result);
    }

    /**
     * Анализ дубликатов дидактических единиц
     * Находит ДЕ с одинаковым текстом и неиспользуемые ДЕ
     */
    public function duplicates(): JsonResponse
    {
        // Получаем все ДЕ с их связями
        $allUnits = DidacticUnit::all();
        
        // Группируем по тексту для поиска дубликатов
        $groupedByText = [];
        foreach ($allUnits as $unit) {
            $text = trim($unit->name);
            if (!isset($groupedByText[$text])) {
                $groupedByText[$text] = collect([]);
            }
            $groupedByText[$text]->push($unit);
        }
        
        // Находим дубликаты (тексты с более чем одной ДЕ)
        $duplicates = [];
        foreach ($groupedByText as $text => $units) {
            if ($units->count() > 1) {
                // Получаем места использования для каждой ДЕ
                $locations = [];
                foreach ($units as $unit) {
                    $unitLocations = $this->getUnitLocations($unit->id);
                    if (!empty($unitLocations)) {
                        $locations = array_merge($locations, $unitLocations);
                    }
                }
                
                // Группируем места по модулю, предмету и компетенции
                $groupedLocations = $this->groupLocations($locations);
                
                $duplicates[] = [
                    'text' => $text,
                    'type' => $units->first()->type,
                    'duplicatesCount' => $units->count(),
                    'unitIds' => $units->pluck('id')->toArray(),
                    'locations' => $groupedLocations
                ];
            }
        }
        
        // Находим неиспользуемые ДЕ (не связанные ни с одним предметом и компетенцией)
        // Учитываем только исходные данные, не версионированные
        $usedUnitIds = SubjectDidacticUnitProfCompetency::whereNull('approved_version_id')
            ->pluck('didactic_unit_id')->unique()->toArray();
        $unusedUnits = DidacticUnit::whereNotIn('id', $usedUnitIds)
            ->select('id', 'name', 'type')
            ->get()
            ->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'type' => $unit->type
                ];
            });
        
        // Статистика
        $statistics = [
            'totalUnits' => $allUnits->count(),
            'uniqueTexts' => count($groupedByText),
            'duplicatesCount' => count($duplicates),
            'unusedCount' => $unusedUnits->count()
        ];
        
        return response()->json([
            'statistics' => $statistics,
            'duplicates' => $duplicates,
            'unusedUnits' => $unusedUnits
        ]);
    }
    
    /**
     * Получить места использования ДЕ
     */
    private function getUnitLocations(int $unitId): array
    {
        $relations = SubjectDidacticUnitProfCompetency::where('didactic_unit_id', $unitId)
            ->whereNull('approved_version_id') // Только исходные данные, не версионированные
            ->with(['profCompetency.modul', 'subject'])
            ->get();
        
        $locations = [];
        foreach ($relations as $relation) {
            $module = $relation->profCompetency->modul;
            $subject = $relation->subject;
            
            if ($module && $subject) {
                $locations[] = [
                    'moduleId' => $module->id,
                    'moduleName' => $module->name,
                    'subjectType' => $relation->subject_type,
                    'subjectId' => $relation->subject_id,
                    'subjectName' => $subject->name ?? '',
                    'competencyId' => $relation->prof_competency_id,
                    'competencyName' => $relation->profCompetency->name ?? ''
                ];
            }
        }
        
        return $locations;
    }
    
    /**
     * Группировать места использования по модулю, предмету и компетенции
     */
    private function groupLocations(array $locations): array
    {
        $grouped = [];
        
        foreach ($locations as $location) {
            $key = "{$location['moduleId']}_{$location['subjectType']}_{$location['subjectId']}_{$location['competencyId']}";
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'moduleId' => $location['moduleId'],
                    'moduleName' => $location['moduleName'],
                    'subjectType' => $location['subjectType'],
                    'subjectId' => $location['subjectId'],
                    'subjectName' => $location['subjectName'],
                    'competencies' => []
                ];
            }
            
            // Добавляем компетенцию, если её еще нет
            $compExists = false;
            foreach ($grouped[$key]['competencies'] as $comp) {
                if ($comp['id'] === $location['competencyId']) {
                    $compExists = true;
                    break;
                }
            }
            
            if (!$compExists) {
                $grouped[$key]['competencies'][] = [
                    'id' => $location['competencyId'],
                    'name' => $location['competencyName']
                ];
            }
        }
        
        return array_values($grouped);
    }
}
