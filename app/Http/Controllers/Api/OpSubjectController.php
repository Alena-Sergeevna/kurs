<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpSubject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OpSubjectController extends Controller
{
    public function index(): JsonResponse
    {
        $subjects = OpSubject::with(['profCompetencies', 'didacticUnits'])->get();
        return response()->json($subjects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prof_competency_ids' => 'array',
            'prof_competency_ids.*' => 'exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $subject = OpSubject::create(['name' => $validated['name']]);

        if (isset($validated['prof_competency_ids'])) {
            $subject->profCompetencies()->attach($validated['prof_competency_ids']);
        }

        if (isset($validated['didactic_unit_ids'])) {
            $subject->didacticUnits()->attach($validated['didactic_unit_ids']);
        }

        return response()->json($subject->load(['profCompetencies', 'didacticUnits']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $subject = OpSubject::with(['profCompetencies', 'didacticUnits'])->findOrFail($id);
        
        // Добавляем ДЕ по ПК
        $profCompetencyId = request()->query('prof_competency_id');
        if ($profCompetencyId) {
            $didacticUnits = \Illuminate\Support\Facades\DB::table('subject_didactic_unit_prof_competency')
                ->where('subject_type', 'op')
                ->where('subject_id', $id)
                ->where('prof_competency_id', $profCompetencyId)
                ->join('didactic_units', 'subject_didactic_unit_prof_competency.didactic_unit_id', '=', 'didactic_units.id')
                ->select('didactic_units.*')
                ->get();
            
            $subject->didactic_units_by_pk = $didacticUnits;
        }
        
        return response()->json($subject);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'prof_competency_ids' => 'array',
            'prof_competency_ids.*' => 'exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $subject = OpSubject::findOrFail($id);
        
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

        return response()->json($subject->load(['profCompetencies', 'didacticUnits']));
    }

    public function destroy(string $id): JsonResponse
    {
        $subject = OpSubject::findOrFail($id);
        $subject->delete();
        return response()->json(['message' => 'ОП успешно удалена'], 200);
    }

    /**
     * Обновить дидактические единицы для ОП по ПК
     */
    public function updateDidacticUnits(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'prof_competency_id' => 'required|exists:prof_competencies,id',
            'didactic_unit_ids' => 'array',
            'didactic_unit_ids.*' => 'exists:didactic_units,id',
        ]);

        $subject = OpSubject::findOrFail($id);
        $profCompetencyId = $validated['prof_competency_id'];
        
        // Удаляем старые связи для этой пары ОП-ПК
        \Illuminate\Support\Facades\DB::table('subject_didactic_unit_prof_competency')
            ->where('subject_id', $id)
            ->where('subject_type', 'op')
            ->where('prof_competency_id', $profCompetencyId)
            ->delete();
        
        // Добавляем новые связи
        $insertData = [];
        foreach (($validated['didactic_unit_ids'] ?? []) as $unitId) {
            $insertData[] = [
                'subject_type' => 'op',
                'subject_id' => $id,
                'didactic_unit_id' => $unitId,
                'prof_competency_id' => $profCompetencyId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($insertData)) {
            \Illuminate\Support\Facades\DB::table('subject_didactic_unit_prof_competency')->insert($insertData);
        }

        return response()->json([
            'message' => 'Дидактические единицы обновлены',
            'subject' => $subject->load(['didacticUnits'])
        ]);
    }
}
