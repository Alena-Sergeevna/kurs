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
            
            // Формируем описание изменений
            $description = [];
            $actions = [];
            
            if ($subjectGroup) {
                foreach ($subjectGroup as $draft) {
                    // Показываем только move и create (новые связи), исключаем keep и remove
                    if (!in_array($draft->action, ['move', 'create'])) {
                        continue;
                    }
                    
                    $actionLabel = $draft->action === 'move' ? 'Перенос' : 'Создание';
                    
                    // Загружаем названия
                    $subjectName = null;
                    $originalCompetencyName = null;
                    $newCompetencyName = null;
                    
                    if ($draft->original_subject_type === 'modul') {
                        $subject = \App\Models\ModulSubject::find($draft->original_subject_id);
                        $subjectName = $subject?->name;
                    } else {
                        $subject = \App\Models\OpSubject::find($draft->original_subject_id);
                        $subjectName = $subject?->name;
                    }
                    
                    $competency = \App\Models\ProfCompetency::find($draft->original_prof_competency_id);
                    $originalCompetencyName = $competency?->name;
                    
                    if ($draft->action === 'move' && $draft->new_prof_competency_id) {
                        $competency = \App\Models\ProfCompetency::find($draft->new_prof_competency_id);
                        $newCompetencyName = $competency?->name;
                    }
                    
                    $subjectTypeLabel = $draft->original_subject_type === 'modul' ? 'МДК' : 'ОП';
                    $desc = "{$subjectTypeLabel}: {$subjectName}";
                    
                    if ($draft->action === 'move' && $newCompetencyName) {
                        $desc .= " → ПК: {$originalCompetencyName} → {$newCompetencyName}";
                    } else {
                        $desc .= " ↔ ПК: {$originalCompetencyName}";
                    }
                    
                    $description[] = $desc;
                    $actions[] = $actionLabel;
                }
            }
            
            if ($unitGroup) {
                // Счетчик для отфильтрованных ДЕ
                $filteredUnitCount = 0;
                // Собираем информацию о новых связях из изменений связей (subjectGroup)
                $newRelations = [];
                $oldRelations = [];
                
                if ($subjectGroup) {
                    foreach ($subjectGroup as $subjectDraft) {
                        if ($subjectDraft->action === 'move') {
                            // Новая связь после переноса
                            $newRelations[] = [
                                'subject_type' => $subjectDraft->new_subject_type ?? $subjectDraft->original_subject_type,
                                'subject_id' => $subjectDraft->new_subject_id ?? $subjectDraft->original_subject_id,
                                'prof_competency_id' => $subjectDraft->new_prof_competency_id ?? $subjectDraft->original_prof_competency_id,
                            ];
                            // Старая связь - исключаем
                            $oldRelations[] = [
                                'subject_type' => $subjectDraft->original_subject_type,
                                'subject_id' => $subjectDraft->original_subject_id,
                                'prof_competency_id' => $subjectDraft->original_prof_competency_id,
                            ];
                        } elseif ($subjectDraft->action === 'create') {
                            // Новая связь
                            $newRelations[] = [
                                'subject_type' => $subjectDraft->new_subject_type ?? $subjectDraft->original_subject_type,
                                'subject_id' => $subjectDraft->new_subject_id ?? $subjectDraft->original_subject_id,
                                'prof_competency_id' => $subjectDraft->new_prof_competency_id ?? $subjectDraft->original_prof_competency_id,
                            ];
                        }
                    }
                }
                
                foreach ($unitGroup as $draft) {
                    // Если есть изменения связей, фильтруем ДЕ - показываем только для новых связей
                    if (!empty($subjectGroup)) {
                        // Проверяем, относится ли эта ДЕ к старой связи (которую перенесли)
                        $isOldRelation = false;
                        foreach ($oldRelations as $oldRelation) {
                            if ($draft->subject_type === $oldRelation['subject_type'] &&
                                $draft->subject_id === $oldRelation['subject_id'] &&
                                $draft->prof_competency_id === $oldRelation['prof_competency_id']) {
                                $isOldRelation = true;
                                break;
                            }
                        }
                        
                        // Если это ДЕ для старой связи, пропускаем
                        if ($isOldRelation) {
                            continue;
                        }
                        
                        // Проверяем, относится ли эта ДЕ к новой связи
                        $isNewRelation = false;
                        foreach ($newRelations as $newRelation) {
                            if ($draft->subject_type === $newRelation['subject_type'] &&
                                $draft->subject_id === $newRelation['subject_id'] &&
                                $draft->prof_competency_id === $newRelation['prof_competency_id']) {
                                $isNewRelation = true;
                                break;
                            }
                        }
                        
                        // Если это ДЕ не для новой связи, пропускаем
                        if (!$isNewRelation) {
                            continue;
                        }
                    }
                    
                    $actionLabel = $draft->action === 'merge' ? 'Объединение' : 
                                  ($draft->action === 'replace' ? 'Замена' : 
                                  ($draft->action === 'create' ? 'Создание' : 
                                  ($draft->action === 'remove' ? 'Удаление' : 'Оставить')));
                    
                    // Загружаем названия
                    $subjectName = null;
                    $competencyName = null;
                    
                    if ($draft->subject_type === 'modul') {
                        $subject = \App\Models\ModulSubject::find($draft->subject_id);
                        $subjectName = $subject?->name;
                    } else {
                        $subject = \App\Models\OpSubject::find($draft->subject_id);
                        $subjectName = $subject?->name;
                    }
                    
                    $competency = \App\Models\ProfCompetency::find($draft->prof_competency_id);
                    $competencyName = $competency?->name;
                    
                    $subjectTypeLabel = $draft->subject_type === 'modul' ? 'МДК' : 'ОП';
                    $desc = "{$subjectTypeLabel}: {$subjectName} ↔ ПК: {$competencyName}";
                    
                    if ($draft->action === 'merge' && $draft->new_didactic_unit_name) {
                        $desc .= " - Объединение в: {$draft->new_didactic_unit_name}";
                    } elseif ($draft->action === 'replace' && $draft->new_didactic_unit_name) {
                        $originalUnitName = \App\Models\DidacticUnit::find($draft->original_didactic_unit_id)?->name ?? "ID: {$draft->original_didactic_unit_id}";
                        $newUnitName = $draft->new_didactic_unit_name ?? (\App\Models\DidacticUnit::find($draft->new_didactic_unit_id)?->name ?? "ID: {$draft->new_didactic_unit_id}");
                        $desc .= " - Замена: {$originalUnitName} → {$newUnitName}";
                    } elseif ($draft->action === 'create' && $draft->new_didactic_unit_name) {
                        $desc .= " - Создание: {$draft->new_didactic_unit_name}";
                    } elseif ($draft->action === 'remove') {
                        $originalUnitName = \App\Models\DidacticUnit::find($draft->original_didactic_unit_id)?->name ?? "ID: {$draft->original_didactic_unit_id}";
                        $desc .= " - Удаление: {$originalUnitName}";
                    }
                    
                    $description[] = $desc;
                    $actions[] = $actionLabel;
                    $filteredUnitCount++; // Увеличиваем счетчик для отфильтрованных ДЕ
                }
            } else {
                $filteredUnitCount = 0;
            }
            
            // Пересчитываем количество изменений связей (только move и create)
            $filteredSubjectCount = 0;
            if ($subjectGroup) {
                foreach ($subjectGroup as $draft) {
                    if (in_array($draft->action, ['move', 'create'])) {
                        $filteredSubjectCount++;
                    }
                }
            }
            
            return [
                'draft_batch_id' => $batchId,
                'created_at' => $subjectGroup?->first()?->created_at 
                    ?? $unitGroup?->first()?->created_at,
                'subject_competency_count' => $filteredSubjectCount,
                'didactic_unit_count' => $filteredUnitCount,
                'description' => $description,
                'actions' => array_unique($actions),
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
        
        // Логируем для отладки
        \Log::info('Subject competency draft created:', [
            'draft_id' => $draft->id,
            'draft_batch_id' => $draft->draft_batch_id,
            'action' => $draft->action,
            'original' => [
                'subject_type' => $draft->original_subject_type,
                'subject_id' => $draft->original_subject_id,
                'prof_competency_id' => $draft->original_prof_competency_id,
            ],
            'new' => [
                'prof_competency_id' => $draft->new_prof_competency_id,
            ]
        ]);

        return response()->json([
            'draft' => $draft,
            'draft_batch_id' => $draft->draft_batch_id
        ], 201);
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
        try {
            $preview = $this->draftService->previewDraft($draftBatchId);
            return response()->json($preview);
        } catch (\Exception $e) {
            \Log::error('Error in show draft:', [
                'draft_batch_id' => $draftBatchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'subject_competency_changes' => [],
                'didactic_unit_changes' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Предпросмотр изменений черновика
     */
    public function preview(string $draftBatchId): JsonResponse
    {
        try {
            $preview = $this->draftService->previewDraft($draftBatchId);
            
            // Логируем для отладки
            \Log::info('Preview for draft batch:', [
                'draft_batch_id' => $draftBatchId,
                'subject_changes_count' => count($preview['subject_competency_changes'] ?? []),
                'unit_changes_count' => count($preview['didactic_unit_changes'] ?? []),
            ]);
            
            return response()->json($preview);
        } catch (\Exception $e) {
            \Log::error('Error in preview:', [
                'draft_batch_id' => $draftBatchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'subject_competency_changes' => [],
                'didactic_unit_changes' => [],
                'error' => $e->getMessage()
            ], 500);
        }
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
     * Получить черновики ДЕ для конкретной связи (без фильтрации по новым/старым связям)
     * Используется для загрузки черновиков для старых связей
     */
    public function getDidacticUnitDraftsForRelation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:modul,op',
            'subject_id' => 'required|integer',
            'prof_competency_id' => 'required|integer',
        ]);

        // Загружаем все черновики ДЕ для этой связи без фильтрации
        $unitDrafts = DidacticUnitDraft::where('subject_type', $validated['subject_type'])
            ->where('subject_id', $validated['subject_id'])
            ->where('prof_competency_id', $validated['prof_competency_id'])
            ->get();

        $changes = $unitDrafts->map(function ($draft) {
            // Загружаем названия
            $subjectName = null;
            $competencyName = null;
            
            if ($draft->subject_type === 'modul') {
                $subject = \App\Models\ModulSubject::find($draft->subject_id);
                $subjectName = $subject?->name;
            } else {
                $subject = \App\Models\OpSubject::find($draft->subject_id);
                $subjectName = $subject?->name;
            }
            
            $competency = \App\Models\ProfCompetency::find($draft->prof_competency_id);
            $competencyName = $competency?->name;
            
            return [
                'id' => $draft->id,
                'action' => $draft->action,
                'context' => [
                    'subject_type' => $draft->subject_type,
                    'subject_id' => $draft->subject_id,
                    'subject_name' => $subjectName,
                    'prof_competency_id' => $draft->prof_competency_id,
                    'competency_name' => $competencyName,
                ],
                'original' => [
                    'didactic_unit_id' => $draft->original_didactic_unit_id,
                    'didactic_unit_ids' => is_array($draft->original_didactic_unit_ids) 
                        ? $draft->original_didactic_unit_ids 
                        : ($draft->original_didactic_unit_ids ? json_decode($draft->original_didactic_unit_ids, true) : null),
                ],
                'new' => [
                    'didactic_unit_id' => $draft->new_didactic_unit_id,
                    'didactic_unit_name' => $draft->new_didactic_unit_name,
                    'didactic_unit_type' => $draft->new_didactic_unit_type,
                ],
                'comment' => $draft->comment,
                'draft_batch_id' => $draft->draft_batch_id,
            ];
        })->values()->toArray();

        return response()->json([
            'didactic_unit_changes' => $changes
        ]);
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
