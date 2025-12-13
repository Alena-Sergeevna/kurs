<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfCompetency;
use App\Services\SubjectProfCompetencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfCompetencyController extends Controller
{
    public function __construct(
        protected SubjectProfCompetencyService $subjectProfCompetencyService
    ) {}
    public function index(Request $request): JsonResponse
    {
        // Загружаем с явным указанием withPivot для правильной загрузки pivot данных
        $query = ProfCompetency::with([
            'modul:id,id,name',
            'modulSubjects' => function($query) {
                // Не используем select, чтобы pivot загрузился правильно
                $query->withPivot('approved', 'subject_type');
            },
            'opSubjects' => function($query) {
                // Не используем select, чтобы pivot загрузился правильно
                $query->withPivot('approved', 'subject_type');
            }
        ]);
        
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

        $competency = $this->subjectProfCompetencyService->syncModulSubjects(
            (int) $id,
            $validated['modul_subject_ids'] ?? [],
            $validated['approve'] ?? false
        );

        return response()->json([
            'message' => 'Связи МДК обновлены',
            'competency' => $competency
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

        $competency = $this->subjectProfCompetencyService->syncOpSubjects(
            (int) $id,
            $validated['op_subject_ids'] ?? [],
            $validated['approve'] ?? false
        );

        return response()->json([
            'message' => 'Связи ОП обновлены',
            'competency' => $competency
        ]);
    }

    /**
     * Утвердить все связи компетенции
     */
    public function approveRelations(string $id): JsonResponse
    {
        $competency = $this->subjectProfCompetencyService->approveAllRelations((int) $id);

        return response()->json([
            'message' => 'Все связи утверждены',
            'competency' => $competency
        ]);
    }

    /**
     * Разутвердить все связи компетенции
     */
    public function unapproveRelations(string $id): JsonResponse
    {
        $competency = $this->subjectProfCompetencyService->unapproveAllRelations((int) $id);

        return response()->json([
            'message' => 'Все связи разутверждены',
            'competency' => $competency
        ]);
    }
}
