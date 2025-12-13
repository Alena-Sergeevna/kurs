<?php

namespace App\Services;

use App\Models\SubjectProfCompetencyDraft;
use App\Models\DidacticUnitDraft;
use App\Models\SubjectProfCompetency;
use App\Models\SubjectDidacticUnitProfCompetency;
use App\Models\DidacticUnit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для работы с черновиками изменений
 */
class DraftService
{
    /**
     * Найти существующий черновик для связи ПК-МДК/ОП
     */
    public function findSubjectCompetencyDraft(string $subjectType, int $subjectId, int $competencyId): ?SubjectProfCompetencyDraft
    {
        return SubjectProfCompetencyDraft::where('original_subject_type', $subjectType)
            ->where('original_subject_id', $subjectId)
            ->where('original_prof_competency_id', $competencyId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Создать или обновить черновик изменения связи ПК-МДК/ОП
     */
    public function createOrUpdateSubjectCompetencyDraft(array $data): SubjectProfCompetencyDraft
    {
        // Ищем существующий черновик
        $existingDraft = $this->findSubjectCompetencyDraft(
            $data['original_subject_type'],
            $data['original_subject_id'],
            $data['original_prof_competency_id']
        );

        $draftBatchId = $data['draft_batch_id'] ?? ($existingDraft?->draft_batch_id ?? Str::uuid()->toString());

        if ($existingDraft) {
            // Обновляем существующий черновик
            $existingDraft->update([
                'new_subject_type' => $data['new_subject_type'] ?? $data['original_subject_type'],
                'new_subject_id' => $data['new_subject_id'] ?? $data['original_subject_id'],
                'new_prof_competency_id' => $data['new_prof_competency_id'] ?? $data['original_prof_competency_id'],
                'action' => $data['action'] ?? 'keep',
                'comment' => $data['comment'] ?? null,
                'draft_batch_id' => $draftBatchId,
            ]);
            return $existingDraft->fresh();
        }

        // Создаем новый черновик
        return SubjectProfCompetencyDraft::create([
            'original_subject_type' => $data['original_subject_type'],
            'original_subject_id' => $data['original_subject_id'],
            'original_prof_competency_id' => $data['original_prof_competency_id'],
            'new_subject_type' => $data['new_subject_type'] ?? $data['original_subject_type'],
            'new_subject_id' => $data['new_subject_id'] ?? $data['original_subject_id'],
            'new_prof_competency_id' => $data['new_prof_competency_id'] ?? $data['original_prof_competency_id'],
            'action' => $data['action'] ?? 'keep',
            'comment' => $data['comment'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'draft_batch_id' => $draftBatchId,
        ]);
    }

    /**
     * Создать черновик изменения связи ПК-МДК/ОП
     */
    public function createSubjectCompetencyDraft(array $data): SubjectProfCompetencyDraft
    {
        return $this->createOrUpdateSubjectCompetencyDraft($data);
    }

    /**
     * Создать черновик изменения ДЕ
     */
    public function createDidacticUnitDraft(array $data): DidacticUnitDraft
    {
        return DidacticUnitDraft::create([
            'draft_batch_id' => $data['draft_batch_id'],
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'prof_competency_id' => $data['prof_competency_id'],
            'original_didactic_unit_id' => $data['original_didactic_unit_id'] ?? null,
            'original_didactic_unit_ids' => $data['original_didactic_unit_ids'] ?? null,
            'new_didactic_unit_id' => $data['new_didactic_unit_id'] ?? null,
            'new_didactic_unit_name' => $data['new_didactic_unit_name'] ?? null,
            'new_didactic_unit_type' => $data['new_didactic_unit_type'] ?? null,
            'action' => $data['action'],
            'target_subject_type' => $data['target_subject_type'] ?? null,
            'target_subject_id' => $data['target_subject_id'] ?? null,
            'target_prof_competency_id' => $data['target_prof_competency_id'] ?? null,
            'comment' => $data['comment'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Получить предпросмотр изменений черновика
     */
    public function previewDraft(string $draftBatchId): array
    {
        $subjectDrafts = SubjectProfCompetencyDraft::where('draft_batch_id', $draftBatchId)->get();
        $unitDrafts = DidacticUnitDraft::where('draft_batch_id', $draftBatchId)->get();

        $subjectChanges = $subjectDrafts->map(function ($draft) {
            return [
                'id' => $draft->id,
                'action' => $draft->action,
                'original' => [
                    'subject_type' => $draft->original_subject_type,
                    'subject_id' => $draft->original_subject_id,
                    'prof_competency_id' => $draft->original_prof_competency_id,
                ],
                'new' => [
                    'subject_type' => $draft->new_subject_type ?? $draft->original_subject_type,
                    'subject_id' => $draft->new_subject_id ?? $draft->original_subject_id,
                    'prof_competency_id' => $draft->new_prof_competency_id ?? $draft->original_prof_competency_id,
                ],
                'comment' => $draft->comment,
            ];
        })->values()->toArray();

        $unitChanges = $unitDrafts->map(function ($draft) {
            return [
                'id' => $draft->id,
                'action' => $draft->action,
                'context' => [
                    'subject_type' => $draft->subject_type,
                    'subject_id' => $draft->subject_id,
                    'prof_competency_id' => $draft->prof_competency_id,
                ],
                'original' => [
                    'didactic_unit_id' => $draft->original_didactic_unit_id,
                    'didactic_unit_ids' => $draft->original_didactic_unit_ids ? json_decode($draft->original_didactic_unit_ids, true) : null,
                ],
                'new' => [
                    'didactic_unit_id' => $draft->new_didactic_unit_id,
                    'didactic_unit_name' => $draft->new_didactic_unit_name,
                    'didactic_unit_type' => $draft->new_didactic_unit_type,
                ],
                'target' => $draft->target_subject_id ? [
                    'subject_type' => $draft->target_subject_type,
                    'subject_id' => $draft->target_subject_id,
                    'prof_competency_id' => $draft->target_prof_competency_id,
                ] : null,
                'comment' => $draft->comment,
            ];
        })->values()->toArray();

        return [
            'subject_competency_changes' => $subjectChanges,
            'didactic_unit_changes' => $unitChanges,
        ];
    }

    /**
     * Применить черновик
     */
    public function applyDraft(string $draftBatchId, VersionService $versionService): array
    {
        return DB::transaction(function () use ($draftBatchId, $versionService) {
            // 1. Валидация - проверяем существование черновика
            $subjectDrafts = SubjectProfCompetencyDraft::where('draft_batch_id', $draftBatchId)->get();
            $unitDrafts = DidacticUnitDraft::where('draft_batch_id', $draftBatchId)->get();
            
            if ($subjectDrafts->isEmpty() && $unitDrafts->isEmpty()) {
                throw new \Exception('Черновик не найден');
            }
            
            // 2. Создание снимка текущего состояния
            $snapshotBefore = $versionService->createSnapshot();
            
            // 3. Применение изменений связей ПК-МДК/ОП
            foreach ($subjectDrafts as $draft) {
                $this->applySubjectCompetencyDraft($draft);
            }
            
            // 4. Применение изменений ДЕ
            foreach ($unitDrafts as $draft) {
                $this->applyDidacticUnitDraft($draft);
            }
            
            // 5. Создание снимка нового состояния
            $snapshotAfter = $versionService->createSnapshot();
            
            // 6. Сохранение версии
            $changesSummary = $this->generateChangesSummary($subjectDrafts, $unitDrafts);
            $versionService->saveVersion($snapshotBefore, $snapshotAfter, $draftBatchId, $changesSummary);
            
            // 7. Удаление примененных черновиков
            SubjectProfCompetencyDraft::where('draft_batch_id', $draftBatchId)->delete();
            DidacticUnitDraft::where('draft_batch_id', $draftBatchId)->delete();
            
            return [
                'success' => true,
                'message' => 'Черновик успешно применен',
                'changes_count' => $subjectDrafts->count() + $unitDrafts->count(),
            ];
        });
    }
    
    /**
     * Применить изменение связи ПК-МДК/ОП
     */
    private function applySubjectCompetencyDraft(SubjectProfCompetencyDraft $draft): void
    {
        switch ($draft->action) {
            case 'keep':
                // Оставить как есть - ничего не делаем
                break;
                
            case 'move':
                // Перенести в другой ПК
                // Удаляем старую связь
                SubjectProfCompetency::where('subject_type', $draft->original_subject_type)
                    ->where('subject_id', $draft->original_subject_id)
                    ->where('prof_competency_id', $draft->original_prof_competency_id)
                    ->delete();
                
                // Создаем новую связь
                SubjectProfCompetency::create([
                    'subject_type' => $draft->new_subject_type ?? $draft->original_subject_type,
                    'subject_id' => $draft->new_subject_id ?? $draft->original_subject_id,
                    'prof_competency_id' => $draft->new_prof_competency_id,
                    'approved' => true,
                ]);
                break;
                
            case 'remove':
                // Удалить связь
                SubjectProfCompetency::where('subject_type', $draft->original_subject_type)
                    ->where('subject_id', $draft->original_subject_id)
                    ->where('prof_competency_id', $draft->original_prof_competency_id)
                    ->delete();
                
                // Также удаляем все связанные ДЕ
                SubjectDidacticUnitProfCompetency::where('subject_type', $draft->original_subject_type)
                    ->where('subject_id', $draft->original_subject_id)
                    ->where('prof_competency_id', $draft->original_prof_competency_id)
                    ->delete();
                break;
        }
    }
    
    /**
     * Применить изменение ДЕ
     */
    private function applyDidacticUnitDraft(DidacticUnitDraft $draft): void
    {
        switch ($draft->action) {
            case 'keep':
                // Оставить как есть - ничего не делаем
                break;
                
            case 'replace':
                // Заменить ДЕ
                if ($draft->original_didactic_unit_id) {
                    // Удаляем старую связь
                    SubjectDidacticUnitProfCompetency::where('subject_type', $draft->subject_type)
                        ->where('subject_id', $draft->subject_id)
                        ->where('prof_competency_id', $draft->prof_competency_id)
                        ->where('didactic_unit_id', $draft->original_didactic_unit_id)
                        ->delete();
                }
                
                // Создаем новую связь
                $newUnitId = $draft->new_didactic_unit_id;
                if (!$newUnitId && $draft->new_didactic_unit_name) {
                    // Создаем новую ДЕ
                    $newUnit = DidacticUnit::create([
                        'name' => $draft->new_didactic_unit_name,
                        'type' => $draft->new_didactic_unit_type,
                    ]);
                    $newUnitId = $newUnit->id;
                }
                
                if ($newUnitId) {
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->subject_type,
                        'subject_id' => $draft->subject_id,
                        'prof_competency_id' => $draft->prof_competency_id,
                        'didactic_unit_id' => $newUnitId,
                    ]);
                }
                break;
                
            case 'move':
                // Перенести ДЕ в другую связь
                if ($draft->original_didactic_unit_id && $draft->target_subject_id) {
                    // Удаляем старую связь
                    SubjectDidacticUnitProfCompetency::where('subject_type', $draft->subject_type)
                        ->where('subject_id', $draft->subject_id)
                        ->where('prof_competency_id', $draft->prof_competency_id)
                        ->where('didactic_unit_id', $draft->original_didactic_unit_id)
                        ->delete();
                    
                    // Создаем новую связь
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->target_subject_type,
                        'subject_id' => $draft->target_subject_id,
                        'prof_competency_id' => $draft->target_prof_competency_id,
                        'didactic_unit_id' => $draft->original_didactic_unit_id,
                    ]);
                }
                break;
                
            case 'merge':
                // Объединить несколько ДЕ в одну
                if ($draft->original_didactic_unit_ids && $draft->new_didactic_unit_name) {
                    // Создаем новую объединенную ДЕ
                    $mergedUnit = DidacticUnit::create([
                        'name' => $draft->new_didactic_unit_name,
                        'type' => $draft->new_didactic_unit_type,
                    ]);
                    
                    // Удаляем старые связи
                    SubjectDidacticUnitProfCompetency::where('subject_type', $draft->subject_type)
                        ->where('subject_id', $draft->subject_id)
                        ->where('prof_competency_id', $draft->prof_competency_id)
                        ->whereIn('didactic_unit_id', $draft->original_didactic_unit_ids)
                        ->delete();
                    
                    // Создаем новую связь с объединенной ДЕ
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->subject_type,
                        'subject_id' => $draft->subject_id,
                        'prof_competency_id' => $draft->prof_competency_id,
                        'didactic_unit_id' => $mergedUnit->id,
                    ]);
                }
                break;
                
            case 'create':
                // Создать новую ДЕ
                if ($draft->new_didactic_unit_name) {
                    $newUnit = DidacticUnit::create([
                        'name' => $draft->new_didactic_unit_name,
                        'type' => $draft->new_didactic_unit_type,
                    ]);
                    
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->subject_type,
                        'subject_id' => $draft->subject_id,
                        'prof_competency_id' => $draft->prof_competency_id,
                        'didactic_unit_id' => $newUnit->id,
                    ]);
                }
                break;
                
            case 'remove':
                // Удалить ДЕ
                if ($draft->original_didactic_unit_id) {
                    SubjectDidacticUnitProfCompetency::where('subject_type', $draft->subject_type)
                        ->where('subject_id', $draft->subject_id)
                        ->where('prof_competency_id', $draft->prof_competency_id)
                        ->where('didactic_unit_id', $draft->original_didactic_unit_id)
                        ->delete();
                }
                break;
        }
    }
    
    /**
     * Сгенерировать краткое описание изменений
     */
    private function generateChangesSummary($subjectDrafts, $unitDrafts): array
    {
        $summary = [
            'subject_competency_changes' => $subjectDrafts->count(),
            'didactic_unit_changes' => $unitDrafts->count(),
            'actions' => [
                'keep' => 0,
                'move' => 0,
                'remove' => 0,
                'replace' => 0,
                'merge' => 0,
                'create' => 0,
            ],
        ];
        
        foreach ($subjectDrafts as $draft) {
            $summary['actions'][$draft->action] = ($summary['actions'][$draft->action] ?? 0) + 1;
        }
        
        foreach ($unitDrafts as $draft) {
            $summary['actions'][$draft->action] = ($summary['actions'][$draft->action] ?? 0) + 1;
        }
        
        return $summary;
    }
}

