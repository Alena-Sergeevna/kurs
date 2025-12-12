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
}
