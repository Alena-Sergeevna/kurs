<?php

namespace App\Services;

use App\Models\SubjectProfCompetencyDraft;
use App\Models\DidacticUnitDraft;
use App\Models\SubjectProfCompetency;
use App\Models\SubjectDidacticUnitProfCompetency;
use App\Models\DidacticUnit;
use App\Models\ApprovedVersion;
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
        // Если есть original_didactic_unit_id, ищем существующий черновик для этой ДЕ
        if (isset($data['original_didactic_unit_id']) && $data['original_didactic_unit_id']) {
            $existingDraft = DidacticUnitDraft::where('draft_batch_id', $data['draft_batch_id'])
                ->where('subject_type', $data['subject_type'])
                ->where('subject_id', $data['subject_id'])
                ->where('prof_competency_id', $data['prof_competency_id'])
                ->where('original_didactic_unit_id', $data['original_didactic_unit_id'])
                ->first();
            
            if ($existingDraft) {
                // Обновляем существующий черновик
                $existingDraft->update([
                    'action' => $data['action'],
                    'new_didactic_unit_id' => $data['new_didactic_unit_id'] ?? null,
                    'new_didactic_unit_name' => $data['new_didactic_unit_name'] ?? null,
                    'new_didactic_unit_type' => $data['new_didactic_unit_type'] ?? null,
                    'target_subject_type' => $data['target_subject_type'] ?? null,
                    'target_subject_id' => $data['target_subject_id'] ?? null,
                    'target_prof_competency_id' => $data['target_prof_competency_id'] ?? null,
                    'comment' => $data['comment'] ?? null,
                ]);
                return $existingDraft;
            }
        }
        
        // Создаем новый черновик
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
        // Логируем для отладки
        \Log::info('Loading preview for draft batch:', ['draft_batch_id' => $draftBatchId]);
        
        $subjectDrafts = SubjectProfCompetencyDraft::where('draft_batch_id', $draftBatchId)->get();
        $unitDrafts = DidacticUnitDraft::where('draft_batch_id', $draftBatchId)->get();
        
        \Log::info('Found drafts:', [
            'subject_drafts_count' => $subjectDrafts->count(),
            'unit_drafts_count' => $unitDrafts->count(),
        ]);

        // Фильтруем изменения связей - показываем только move и create (новые связи)
        // Исключаем keep и remove, так как они не создают новых связей
        $subjectChanges = $subjectDrafts->filter(function ($draft) {
            // Показываем только move и create, так как они создают новые связи
            return in_array($draft->action, ['move', 'create']);
        })->map(function ($draft) {
            // Загружаем названия предметов и компетенций
            $originalSubjectName = null;
            $newSubjectName = null;
            $originalCompetencyName = null;
            $newCompetencyName = null;
            
            if ($draft->original_subject_type === 'modul') {
                $subject = \App\Models\ModulSubject::find($draft->original_subject_id);
                $originalSubjectName = $subject?->name;
            } else {
                $subject = \App\Models\OpSubject::find($draft->original_subject_id);
                $originalSubjectName = $subject?->name;
            }
            
            if ($draft->new_subject_id && $draft->new_subject_id !== $draft->original_subject_id) {
                if ($draft->new_subject_type === 'modul') {
                    $subject = \App\Models\ModulSubject::find($draft->new_subject_id);
                    $newSubjectName = $subject?->name;
                } else {
                    $subject = \App\Models\OpSubject::find($draft->new_subject_id);
                    $newSubjectName = $subject?->name;
                }
            } else {
                $newSubjectName = $originalSubjectName;
            }
            
            $competency = \App\Models\ProfCompetency::find($draft->original_prof_competency_id);
            $originalCompetencyName = $competency?->name;
            
            if ($draft->new_prof_competency_id && $draft->new_prof_competency_id !== $draft->original_prof_competency_id) {
                $competency = \App\Models\ProfCompetency::find($draft->new_prof_competency_id);
                $newCompetencyName = $competency?->name;
            } else {
                $newCompetencyName = $originalCompetencyName;
            }
            
            return [
                'id' => $draft->id,
                'action' => $draft->action,
                'original' => [
                    'subject_type' => $draft->original_subject_type,
                    'subject_id' => $draft->original_subject_id,
                    'subject_name' => $originalSubjectName,
                    'prof_competency_id' => $draft->original_prof_competency_id,
                    'competency_name' => $originalCompetencyName,
                ],
                'new' => [
                    'subject_type' => $draft->new_subject_type ?? $draft->original_subject_type,
                    'subject_id' => $draft->new_subject_id ?? $draft->original_subject_id,
                    'subject_name' => $newSubjectName,
                    'prof_competency_id' => $draft->new_prof_competency_id ?? $draft->original_prof_competency_id,
                    'competency_name' => $newCompetencyName,
                ],
                'comment' => $draft->comment,
            ];
        })->values()->toArray();

        // Собираем информацию о новых связях из изменений связей
        $newRelations = [];
        $oldRelations = []; // Старые связи, которые нужно исключить
        
        foreach ($subjectChanges as $change) {
            if ($change['action'] === 'move') {
                // Для переноса - новая связь
                $newRelations[] = [
                    'subject_type' => $change['new']['subject_type'],
                    'subject_id' => $change['new']['subject_id'],
                    'prof_competency_id' => $change['new']['prof_competency_id'],
                ];
                // Старая связь - исключаем из показа
                $oldRelations[] = [
                    'subject_type' => $change['original']['subject_type'],
                    'subject_id' => $change['original']['subject_id'],
                    'prof_competency_id' => $change['original']['prof_competency_id'],
                ];
            } elseif ($change['action'] === 'create') {
                // Для создания - новая связь
                $newRelations[] = [
                    'subject_type' => $change['new']['subject_type'],
                    'subject_id' => $change['new']['subject_id'],
                    'prof_competency_id' => $change['new']['prof_competency_id'],
                ];
            }
        }

        \Log::info('Filtering unit drafts:', [
            'new_relations' => $newRelations,
            'old_relations' => $oldRelations,
            'total_unit_drafts' => $unitDrafts->count(),
        ]);

        // Фильтруем изменения ДЕ - показываем только те, которые относятся к новым связям
        // ВАЖНО: Если в батче есть изменения связей (move), показываем только ДЕ для новых связей
        // Если изменений связей нет, это может быть черновик только для ДЕ - показываем все
        
        $unitChanges = $unitDrafts->filter(function ($draft) use ($newRelations, $oldRelations, $subjectChanges) {
            // Если есть изменения связей (move/create), фильтруем строго
            if (!empty($subjectChanges)) {
                // Если нет новых связей, не показываем никакие ДЕ
                if (empty($newRelations)) {
                    \Log::info('No new relations, excluding all unit drafts');
                    return false;
                }
                
                // СНАЧАЛА исключаем ДЕ для старых связей (которые были перенесены)
                foreach ($oldRelations as $oldRelation) {
                    if ($draft->subject_type === $oldRelation['subject_type'] &&
                        $draft->subject_id === $oldRelation['subject_id'] &&
                        $draft->prof_competency_id === $oldRelation['prof_competency_id']) {
                        \Log::info('Excluding unit draft for old relation:', [
                            'draft_id' => $draft->id,
                            'draft_relation' => [
                                'subject_type' => $draft->subject_type,
                                'subject_id' => $draft->subject_id,
                                'prof_competency_id' => $draft->prof_competency_id,
                            ],
                            'old_relation' => $oldRelation,
                        ]);
                        return false; // Это ДЕ для старой связи, исключаем
                    }
                }
                
                // Затем проверяем, относится ли эта ДЕ к одной из новых связей
                foreach ($newRelations as $newRelation) {
                    if ($draft->subject_type === $newRelation['subject_type'] &&
                        $draft->subject_id === $newRelation['subject_id'] &&
                        $draft->prof_competency_id === $newRelation['prof_competency_id']) {
                        \Log::info('Including unit draft for new relation:', [
                            'draft_id' => $draft->id,
                            'draft_relation' => [
                                'subject_type' => $draft->subject_type,
                                'subject_id' => $draft->subject_id,
                                'prof_competency_id' => $draft->prof_competency_id,
                            ],
                            'new_relation' => $newRelation,
                        ]);
                        return true; // Это ДЕ для новой связи
                    }
                }
                
                // Если ДЕ не относится ни к новой, ни к старой связи из изменений, исключаем
                \Log::info('Excluding unit draft - not matching any relation:', [
                    'draft_id' => $draft->id,
                    'draft_relation' => [
                        'subject_type' => $draft->subject_type,
                        'subject_id' => $draft->subject_id,
                        'prof_competency_id' => $draft->prof_competency_id,
                    ],
                ]);
                return false;
            } else {
                // Если нет изменений связей, это может быть черновик только для ДЕ
                // В этом случае показываем все ДЕ (они не относятся к переносу)
                return true;
            }
        })->map(function ($draft) {
            // Загружаем названия предмета и компетенции
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
                'target' => $draft->target_subject_id ? [
                    'subject_type' => $draft->target_subject_type,
                    'subject_id' => $draft->target_subject_id,
                    'prof_competency_id' => $draft->target_prof_competency_id,
                ] : null,
                'comment' => $draft->comment,
            ];
        })->values()->toArray();

        $result = [
            'subject_competency_changes' => $subjectChanges,
            'didactic_unit_changes' => $unitChanges,
        ];
        
        // Логируем результат
        \Log::info('Preview result:', [
            'draft_batch_id' => $draftBatchId,
            'subject_changes_count' => count($subjectChanges),
            'unit_changes_count' => count($unitChanges),
            'subject_changes' => $subjectChanges,
            'unit_changes' => $unitChanges,
        ]);
        
        return $result;
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
            
            // 2. Создание снимка текущего состояния (исходные данные)
            $snapshotBefore = $versionService->createSnapshot();
            
            // 3. Получаем номер новой версии
            $newVersionNumber = (ApprovedVersion::max('version_number') ?? 0) + 1;
            
            // 4. Применение изменений связей ПК-МДК/ОП (создаем новые записи, старые не удаляем)
            foreach ($subjectDrafts as $draft) {
                $this->applySubjectCompetencyDraft($draft, $newVersionNumber);
            }
            
            // 5. Применение изменений ДЕ (создаем новые записи, старые не удаляем)
            foreach ($unitDrafts as $draft) {
                $this->applyDidacticUnitDraft($draft, $newVersionNumber);
            }
            
            // 6. Создание снимка нового состояния (включая новые версии)
            $snapshotAfter = $versionService->createSnapshotAfter();
            
            // 7. Сохранение версии
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
    private function applySubjectCompetencyDraft(SubjectProfCompetencyDraft $draft, int $versionNumber): void
    {
        switch ($draft->action) {
            case 'keep':
                // Оставить как есть - ничего не делаем
                break;
                
            case 'move':
                // Перенести в другой ПК
                // НЕ удаляем старую связь - она остается как исходная версия (approved_version_id = null)
                // Создаем новую связь с версией
                SubjectProfCompetency::create([
                    'subject_type' => $draft->new_subject_type ?? $draft->original_subject_type,
                    'subject_id' => $draft->new_subject_id ?? $draft->original_subject_id,
                    'prof_competency_id' => $draft->new_prof_competency_id,
                    'approved' => true,
                    'approved_version_id' => $versionNumber,
                ]);
                break;
                
            case 'remove':
                // НЕ удаляем связь физически - она остается как исходная версия
                // Помечаем как удаленную в новой версии (через approved_version_id)
                // Старые данные остаются в базе для истории
                break;
        }
    }
    
    /**
     * Применить изменение ДЕ
     */
    private function applyDidacticUnitDraft(DidacticUnitDraft $draft, int $versionNumber): void
    {
        switch ($draft->action) {
            case 'keep':
                // Оставить как есть - ничего не делаем
                break;
                
            case 'replace':
                // Заменить ДЕ
                // НЕ удаляем старую связь - она остается как исходная версия
                // Создаем новую связь с версией
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
                        'approved_version_id' => $versionNumber,
                    ]);
                }
                break;
                
            case 'move':
                // Перенести ДЕ в другую связь
                if ($draft->original_didactic_unit_id && $draft->target_subject_id) {
                    // НЕ удаляем старую связь - она остается как исходная версия
                    // Создаем новую связь с версией
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->target_subject_type,
                        'subject_id' => $draft->target_subject_id,
                        'prof_competency_id' => $draft->target_prof_competency_id,
                        'didactic_unit_id' => $draft->original_didactic_unit_id,
                        'approved_version_id' => $versionNumber,
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
                    
                    // НЕ удаляем старые связи - они остаются как исходная версия
                    // Создаем новую связь с объединенной ДЕ и версией
                    SubjectDidacticUnitProfCompetency::create([
                        'subject_type' => $draft->subject_type,
                        'subject_id' => $draft->subject_id,
                        'prof_competency_id' => $draft->prof_competency_id,
                        'didactic_unit_id' => $mergedUnit->id,
                        'approved_version_id' => $versionNumber,
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
                        'approved_version_id' => $versionNumber,
                    ]);
                }
                break;
                
            case 'remove':
                // Удалить ДЕ
                // НЕ удаляем физически - старая связь остается как исходная версия
                // В новой версии эта ДЕ просто отсутствует
                break;
        }
    }
    
    /**
     * Сгенерировать краткое описание изменений
     */
    private function generateChangesSummary($subjectDrafts, $unitDrafts): array
    {
        $subjectChanges = [];
        $unitChanges = [];
        
        foreach ($subjectDrafts as $draft) {
            // Загружаем названия
            $originalSubjectName = null;
            $newSubjectName = null;
            $originalCompetencyName = null;
            $newCompetencyName = null;
            
            if ($draft->original_subject_type === 'modul') {
                $subject = \App\Models\ModulSubject::find($draft->original_subject_id);
                $originalSubjectName = $subject?->name;
            } else {
                $subject = \App\Models\OpSubject::find($draft->original_subject_id);
                $originalSubjectName = $subject?->name;
            }
            
            if ($draft->new_subject_id && $draft->new_subject_id !== $draft->original_subject_id) {
                if ($draft->new_subject_type === 'modul') {
                    $subject = \App\Models\ModulSubject::find($draft->new_subject_id);
                    $newSubjectName = $subject?->name;
                } else {
                    $subject = \App\Models\OpSubject::find($draft->new_subject_id);
                    $newSubjectName = $subject?->name;
                }
            } else {
                $newSubjectName = $originalSubjectName;
            }
            
            $competency = \App\Models\ProfCompetency::find($draft->original_prof_competency_id);
            $originalCompetencyName = $competency?->name;
            
            if ($draft->new_prof_competency_id && $draft->new_prof_competency_id !== $draft->original_prof_competency_id) {
                $competency = \App\Models\ProfCompetency::find($draft->new_prof_competency_id);
                $newCompetencyName = $competency?->name;
            } else {
                $newCompetencyName = $originalCompetencyName;
            }
            
            $subjectChanges[] = [
                'action' => $draft->action,
                'subject_type' => $draft->original_subject_type,
                'subject_id' => $draft->original_subject_id,
                'subject_name' => $originalSubjectName,
                'prof_competency_id' => $draft->original_prof_competency_id,
                'competency_name' => $originalCompetencyName,
                'new_prof_competency_id' => $draft->new_prof_competency_id,
                'new_competency_name' => $newCompetencyName,
                'original_competency_name' => $originalCompetencyName,
            ];
        }
        
        foreach ($unitDrafts as $draft) {
            // Загружаем названия
            $subjectName = null;
            $competencyName = null;
            $originalUnitName = null;
            $newUnitName = null;
            
            if ($draft->subject_type === 'modul') {
                $subject = \App\Models\ModulSubject::find($draft->subject_id);
                $subjectName = $subject?->name;
            } else {
                $subject = \App\Models\OpSubject::find($draft->subject_id);
                $subjectName = $subject?->name;
            }
            
            $competency = \App\Models\ProfCompetency::find($draft->prof_competency_id);
            $competencyName = $competency?->name;
            
            if ($draft->original_didactic_unit_id) {
                $unit = \App\Models\DidacticUnit::find($draft->original_didactic_unit_id);
                $originalUnitName = $unit?->name;
            }
            
            if ($draft->new_didactic_unit_id) {
                $unit = \App\Models\DidacticUnit::find($draft->new_didactic_unit_id);
                $newUnitName = $unit?->name;
            } elseif ($draft->new_didactic_unit_name) {
                $newUnitName = $draft->new_didactic_unit_name;
            }
            
            $unitChanges[] = [
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
                    'didactic_unit_name' => $originalUnitName,
                ],
                'new' => [
                    'didactic_unit_id' => $draft->new_didactic_unit_id,
                    'didactic_unit_name' => $newUnitName,
                ],
            ];
        }
        
        return [
            'subject_competency_changes' => $subjectChanges,
            'didactic_unit_changes' => $unitChanges,
        ];
    }
}

