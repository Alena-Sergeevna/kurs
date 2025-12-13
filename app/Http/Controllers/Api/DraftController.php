<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DraftService;
use App\Services\VersionService;
use App\Models\SubjectProfCompetencyDraft;
use App\Models\DidacticUnitDraft;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DraftController extends Controller
{
    public function __construct(
        protected DraftService $draftService,
        protected VersionService $versionService
    ) {}

    /**
     * Получить список всех черновиков
     */
    public function index(Request $request): JsonResponse
    {
        $subjectDrafts = SubjectProfCompetencyDraft::with([])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('draft_batch_id');

        $unitDrafts = DidacticUnitDraft::with([])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('draft_batch_id');

        // Отладочная информация
        \Log::info('Subject drafts count:', ['count' => $subjectDrafts->count()]);
        \Log::info('Unit drafts count:', ['count' => $unitDrafts->count()]);
        \Log::info('Subject draft batch IDs:', $subjectDrafts->keys()->toArray());
        \Log::info('Unit draft batch IDs:', $unitDrafts->keys()->toArray());

        // Объединяем черновики по draft_batch_id
        $allBatchIds = collect($subjectDrafts->keys())->merge($unitDrafts->keys())->unique();
        
        $drafts = $allBatchIds->map(function ($batchId) use ($subjectDrafts, $unitDrafts) {
            $subjectGroup = $subjectDrafts->get($batchId);
            $unitGroup = $unitDrafts->get($batchId);
            
            \Log::info("Processing batch {$batchId}", [
                'subject_count' => $subjectGroup ? $subjectGroup->count() : 0,
                'unit_count' => $unitGroup ? $unitGroup->count() : 0,
            ]);
            
            return [
                'draft_batch_id' => $batchId,
                'created_at' => $subjectGroup?->first()?->created_at 
                    ?? $unitGroup?->first()?->created_at,
                'subject_competency_count' => $subjectGroup ? $subjectGroup->count() : 0,
                'didactic_unit_count' => $unitGroup ? $unitGroup->count() : 0,
            ];
        })->values();

        return response()->json($drafts);
    }

    /**
     * Найти существующий черновик для связи ПК-МДК/ОП
     */
    public function findSubjectCompetencyDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:modul,op',
            'subject_id' => 'required|integer',
            'prof_competency_id' => 'required|integer|exists:prof_competencies,id',
        ]);

        $draft = $this->draftService->findSubjectCompetencyDraft(
            $validated['subject_type'],
            $validated['subject_id'],
            $validated['prof_competency_id']
        );

        if (!$draft) {
            return response()->json(['draft' => null], 200);
        }

        return response()->json(['draft' => $draft], 200);
    }

    /**
     * Создать черновик изменения связи ПК-МДК/ОП
     */
    public function createSubjectCompetencyDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'original_subject_type' => 'required|in:modul,op',
            'original_subject_id' => 'required|integer',
            'original_prof_competency_id' => 'required|integer|exists:prof_competencies,id',
            'new_subject_type' => 'sometimes|in:modul,op',
            'new_subject_id' => 'sometimes|integer',
            'new_prof_competency_id' => 'sometimes|integer|exists:prof_competencies,id',
            'action' => 'required|in:keep,move,remove',
            'comment' => 'nullable|string|max:1000',
            'draft_batch_id' => 'nullable|uuid',
        ]);

        $draft = $this->draftService->createSubjectCompetencyDraft($validated);

        return response()->json($draft, 201);
    }

    /**
     * Создать черновик изменения ДЕ
     */
    public function createDidacticUnitDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_batch_id' => 'required|uuid',
            'subject_type' => 'required|in:modul,op',
            'subject_id' => 'required|integer',
            'prof_competency_id' => 'required|integer|exists:prof_competencies,id',
            'original_didactic_unit_id' => 'nullable|integer|exists:didactic_units,id',
            'original_didactic_unit_ids' => 'nullable|array',
            'original_didactic_unit_ids.*' => 'integer|exists:didactic_units,id',
            'new_didactic_unit_id' => 'nullable|integer|exists:didactic_units,id',
            'new_didactic_unit_name' => 'nullable|string|max:255',
            'new_didactic_unit_type' => 'nullable|in:know,be_able,have_practical_experience',
            'action' => 'required|in:keep,replace,move,merge,create,remove',
            'target_subject_type' => 'nullable|in:modul,op',
            'target_subject_id' => 'nullable|integer',
            'target_prof_competency_id' => 'nullable|integer|exists:prof_competencies,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        $draft = $this->draftService->createDidacticUnitDraft($validated);

        return response()->json($draft, 201);
    }

    /**
     * Получить детали черновика
     */
    public function show(string $draftBatchId): JsonResponse
    {
        $preview = $this->draftService->previewDraft($draftBatchId);

        return response()->json($preview);
    }

    /**
     * Предпросмотр изменений черновика
     */
    public function preview(string $draftBatchId): JsonResponse
    {
        $preview = $this->draftService->previewDraft($draftBatchId);

        return response()->json($preview);
    }

    /**
     * Применить черновик
     */
    public function apply(Request $request, string $draftBatchId): JsonResponse
    {
        try {
            // Применяем черновик (внутри уже создаются снимки и сохраняется версия)
            $result = $this->draftService->applyDraft($draftBatchId, $this->versionService);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'changes_count' => $result['changes_count'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка применения черновика: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Удалить черновик
     */
    public function destroy(string $draftBatchId): JsonResponse
    {
        SubjectProfCompetencyDraft::where('draft_batch_id', $draftBatchId)->delete();
        DidacticUnitDraft::where('draft_batch_id', $draftBatchId)->delete();

        return response()->json(['message' => 'Черновик удален'], 200);
    }
}
