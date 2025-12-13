<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubjectDidacticUnitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class BaseSubjectController extends Controller
{
    public function __construct(
        protected SubjectDidacticUnitService $subjectDidacticUnitService
    ) {}
    /**
     * Получить тип предмета ('modul' или 'op')
     */
    abstract protected function getSubjectType(): string;

    /**
     * Получить модель предмета
     */
    abstract protected function getModelClass(): string;

    /**
     * Получить название предмета для сообщений
     */
    abstract protected function getSubjectName(): string;

    /**
     * Показать предмет с дидактическими единицами по ПК
     */
    public function show(string $id): JsonResponse
    {
        $modelClass = $this->getModelClass();
        $subject = $modelClass::with($this->getDefaultRelations())->findOrFail($id);
        
        // Добавляем ДЕ по ПК используя Eloquent relationship
        $profCompetencyId = request()->query('prof_competency_id');
        if ($profCompetencyId) {
            $didacticUnits = $subject->didacticUnitsByCompetency($profCompetencyId)->get();
            $subject->didactic_units_by_pk = $didacticUnits;
        }
        
        return response()->json($subject);
    }

    /**
     * Обновить дидактические единицы для предмета по ПК
     */
    public function updateDidacticUnits(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'prof_competency_id' => 'required|exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $modelClass = $this->getModelClass();
        $subject = $modelClass::findOrFail($id);
        $subjectType = $this->getSubjectType();
        
        $this->subjectDidacticUnitService->syncDidacticUnits(
            $subjectType,
            (int) $id,
            (int) $validated['prof_competency_id'],
            $validated['didactic_unit_ids'] ?? [],
            $validated['approve'] ?? false
        );

        return response()->json([
            'message' => 'Дидактические единицы обновлены',
            'subject' => $subject->load($this->getLoadRelations())
        ]);
    }

    /**
     * Получить компетенции для предмета
     */
    public function getCompetencies(string $id): JsonResponse
    {
        $modelClass = $this->getModelClass();
        $subject = $modelClass::with('profCompetencies')->findOrFail($id);
        
        $competencies = $subject->profCompetencies->map(function($comp) {
            return [
                'id' => $comp->id,
                'name' => $comp->name
            ];
        });

        return response()->json($competencies);
    }

    /**
     * Получить отношения по умолчанию для загрузки
     */
    protected function getDefaultRelations(): array
    {
        return ['profCompetencies', 'didacticUnits'];
    }

    /**
     * Получить отношения для загрузки после обновления
     */
    protected function getLoadRelations(): array
    {
        return ['didacticUnits'];
    }
}

