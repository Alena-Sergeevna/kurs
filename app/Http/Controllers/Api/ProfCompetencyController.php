<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfCompetency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfCompetencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProfCompetency::with(['modul', 'modulSubjects', 'opSubjects']);
        
        if ($request->has('module_id')) {
            $query->where('id_module', $request->module_id);
        }
        
        $competencies = $query->get();
        return response()->json($competencies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_module' => 'required|exists:moduls,id',
        ]);

        $competency = ProfCompetency::create($validated);
        return response()->json($competency->load(['modul']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $competency = ProfCompetency::with(['modul', 'modulSubjects', 'opSubjects'])->findOrFail($id);
        return response()->json($competency);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'id_module' => 'sometimes|exists:moduls,id',
        ]);

        $competency = ProfCompetency::findOrFail($id);
        $competency->update($validated);
        return response()->json($competency->load(['modul']));
    }

    public function destroy(string $id): JsonResponse
    {
        $competency = ProfCompetency::findOrFail($id);
        $competency->delete();
        return response()->json(['message' => 'Профессиональная компетенция успешно удалена'], 200);
    }

    /**
     * Обновить связи МДК с компетенцией
     */
    public function updateModulSubjects(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'modul_subject_ids' => 'array',
            'modul_subject_ids.*' => 'exists:modulsubjects,id',
            'approve' => 'sometimes|boolean',
        ]);

        $competency = ProfCompetency::findOrFail($id);
        
        // Удаляем старые связи для этого типа
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'modul')
            ->delete();
        
        // Добавляем новые связи
        $insertData = [];
        foreach (($validated['modul_subject_ids'] ?? []) as $subjectId) {
            $insertData[] = [
                'subject_type' => 'modul',
                'subject_id' => $subjectId,
                'prof_competency_id' => $id,
                'approved' => $validated['approve'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($insertData)) {
            DB::table('subject_prof_competency')->insert($insertData);
        }

        return response()->json([
            'message' => 'Связи МДК обновлены',
            'competency' => $competency->load(['modulSubjects', 'opSubjects'])
        ]);
    }

    /**
     * Обновить связи ОП с компетенцией
     */
    public function updateOpSubjects(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'op_subject_ids' => 'array',
            'op_subject_ids.*' => 'exists:op_subjects,id',
            'approve' => 'sometimes|boolean',
        ]);

        $competency = ProfCompetency::findOrFail($id);
        
        // Удаляем старые связи для этого типа
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'op')
            ->delete();
        
        // Добавляем новые связи
        $insertData = [];
        foreach (($validated['op_subject_ids'] ?? []) as $subjectId) {
            $insertData[] = [
                'subject_type' => 'op',
                'subject_id' => $subjectId,
                'prof_competency_id' => $id,
                'approved' => $validated['approve'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($insertData)) {
            DB::table('subject_prof_competency')->insert($insertData);
        }

        return response()->json([
            'message' => 'Связи ОП обновлены',
            'competency' => $competency->load(['modulSubjects', 'opSubjects'])
        ]);
    }

    /**
     * Утвердить все связи компетенции
     */
    public function approveRelations(string $id): JsonResponse
    {
        $competency = ProfCompetency::findOrFail($id);
        
        // Утверждаем все связи МДК
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'modul')
            ->update(['approved' => true, 'updated_at' => now()]);
        
        // Утверждаем все связи ОП
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'op')
            ->update(['approved' => true, 'updated_at' => now()]);

        return response()->json([
            'message' => 'Все связи утверждены',
            'competency' => $competency->load(['modulSubjects', 'opSubjects'])
        ]);
    }

    /**
     * Разутвердить все связи компетенции
     */
    public function unapproveRelations(string $id): JsonResponse
    {
        $competency = ProfCompetency::findOrFail($id);
        
        // Разутверждаем все связи МДК
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'modul')
            ->update(['approved' => false, 'updated_at' => now()]);
        
        // Разутверждаем все связи ОП
        DB::table('subject_prof_competency')
            ->where('prof_competency_id', $id)
            ->where('subject_type', 'op')
            ->update(['approved' => false, 'updated_at' => now()]);

        return response()->json([
            'message' => 'Все связи разутверждены',
            'competency' => $competency->load(['modulSubjects', 'opSubjects'])
        ]);
    }
}
