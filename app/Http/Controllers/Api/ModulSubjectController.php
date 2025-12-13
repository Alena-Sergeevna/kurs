<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModulSubject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModulSubjectController extends BaseSubjectController
{
    protected function getSubjectType(): string
    {
        return 'modul';
    }

    protected function getModelClass(): string
    {
        return ModulSubject::class;
    }

    protected function getSubjectName(): string
    {
        return 'МДК';
    }

    protected function getDefaultRelations(): array
    {
        return ['modul', 'profCompetencies', 'didacticUnits'];
    }

    protected function getLoadRelations(): array
    {
        return ['didacticUnits', 'modul'];
    }
    public function index(): JsonResponse
    {
        $subjects = ModulSubject::with([
            'modul:id,id,name',
            'profCompetencies:id,name',
            'didacticUnits:id,type,name'
        ])->get();
        return response()->json($subjects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_module' => 'required|exists:moduls,id',
            'prof_competency_ids' => 'array',
            'prof_competency_ids.*' => 'exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $subject = ModulSubject::create([
            'name' => $validated['name'],
            'id_module' => $validated['id_module'],
        ]);

        if (isset($validated['prof_competency_ids'])) {
            $subject->profCompetencies()->attach($validated['prof_competency_ids']);
        }

        if (isset($validated['didactic_unit_ids'])) {
            $subject->didacticUnits()->attach($validated['didactic_unit_ids']);
        }

        return response()->json($subject->load(['modul', 'profCompetencies', 'didacticUnits']), 201);
    }

    // Метод show() наследуется от BaseSubjectController

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'id_module' => 'sometimes|exists:moduls,id',
            'prof_competency_ids' => 'array',
            'prof_competency_ids.*' => 'exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $subject = ModulSubject::findOrFail($id);
        
        $updateData = array_filter($validated, function($key) {
            return !in_array($key, ['prof_competency_ids', 'didactic_unit_ids']);
        }, ARRAY_FILTER_USE_KEY);
        
        if (!empty($updateData)) {
            $subject->update($updateData);
        }

        if (isset($validated['prof_competency_ids'])) {
            $subject->profCompetencies()->sync($validated['prof_competency_ids']);
        }

        if (isset($validated['didactic_unit_ids'])) {
            $subject->didacticUnits()->sync($validated['didactic_unit_ids']);
        }

        return response()->json($subject->load(['modul', 'profCompetencies', 'didacticUnits']));
    }

    public function destroy(string $id): JsonResponse
    {
        $subject = ModulSubject::findOrFail($id);
        $subject->delete();
        return response()->json(['message' => 'МДК успешно удален'], 200);
    }

    // Методы updateDidacticUnits() и getCompetencies() наследуются от BaseSubjectController
}
