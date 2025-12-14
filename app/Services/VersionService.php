<?php

namespace App\Services;

use App\Models\ApprovedVersion;
use App\Models\SubjectProfCompetency;
use App\Models\SubjectDidacticUnitProfCompetency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сервис для работы с версиями и сравнением
 */
class VersionService
{
    /**
     * Создать снимок текущего состояния системы
     */
    public function createSnapshot(): array
    {
        // Создаем снимок только исходных данных (без версий) для snapshot_before
        // И всех данных (включая версии) для snapshot_after
        $subjectCompetencies = SubjectProfCompetency::whereNull('approved_version_id')->get()->map(function ($item) {
            return [
                'subject_type' => $item->subject_type,
                'subject_id' => $item->subject_id,
                'prof_competency_id' => $item->prof_competency_id,
                'approved' => $item->approved,
            ];
        });

        $didacticUnits = SubjectDidacticUnitProfCompetency::whereNull('approved_version_id')->get()->map(function ($item) {
            return [
                'subject_type' => $item->subject_type,
                'subject_id' => $item->subject_id,
                'didactic_unit_id' => $item->didactic_unit_id,
                'prof_competency_id' => $item->prof_competency_id,
            ];
        });

        return [
            'subject_competencies' => $subjectCompetencies,
            'didactic_units' => $didacticUnits,
            'created_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Создать снимок всех данных (включая версионированные) для snapshot_after
     */
    public function createSnapshotAfter(): array
    {
        // Создаем снимок всех данных (включая версии) для snapshot_after
        $subjectCompetencies = SubjectProfCompetency::all()->map(function ($item) {
            return [
                'subject_type' => $item->subject_type,
                'subject_id' => $item->subject_id,
                'prof_competency_id' => $item->prof_competency_id,
                'approved' => $item->approved,
                'approved_version_id' => $item->approved_version_id,
            ];
        });

        $didacticUnits = SubjectDidacticUnitProfCompetency::all()->map(function ($item) {
            return [
                'subject_type' => $item->subject_type,
                'subject_id' => $item->subject_id,
                'didactic_unit_id' => $item->didactic_unit_id,
                'prof_competency_id' => $item->prof_competency_id,
                'approved_version_id' => $item->approved_version_id,
            ];
        });

        return [
            'subject_competencies' => $subjectCompetencies,
            'didactic_units' => $didacticUnits,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Сохранить утвержденную версию
     */
    public function saveVersion(array $snapshotBefore, array $snapshotAfter, string $draftBatchId = null, string|array $changesSummary = null): ApprovedVersion
    {
        $lastVersion = ApprovedVersion::max('version_number') ?? 0;

        // Преобразуем массив в JSON строку, если передан массив
        $summaryString = is_array($changesSummary) ? json_encode($changesSummary, JSON_UNESCAPED_UNICODE) : $changesSummary;

        return ApprovedVersion::create([
            'version_number' => $lastVersion + 1,
            'approved_by' => null, // TODO: добавить когда будет авторизация
            'approved_at' => now(),
            'changes_summary' => $summaryString,
            'snapshot_before' => $snapshotBefore,
            'snapshot_after' => $snapshotAfter,
            'draft_batch_id' => $draftBatchId,
        ]);
    }

    /**
     * Сравнить две версии
     */
    public function compareVersions(int $version1Id, int $version2Id): array
    {
        $version1 = ApprovedVersion::findOrFail($version1Id);
        $version2 = ApprovedVersion::findOrFail($version2Id);

        return [
            'version1' => [
                'id' => $version1->id,
                'version_number' => $version1->version_number,
                'approved_at' => $version1->approved_at,
                'snapshot' => $version1->snapshot_after ?? $version1->snapshot_before,
            ],
            'version2' => [
                'id' => $version2->id,
                'version_number' => $version2->version_number,
                'approved_at' => $version2->approved_at,
                'snapshot' => $version2->snapshot_after ?? $version2->snapshot_before,
            ],
            'differences' => $this->calculateDifferences(
                $version1->snapshot_after ?? $version1->snapshot_before,
                $version2->snapshot_after ?? $version2->snapshot_before
            ),
        ];
    }

    /**
     * Вычислить различия между двумя снимками
     */
    private function calculateDifferences(array $snapshot1, array $snapshot2): array
    {
        // TODO: Реализовать детальное сравнение
        return [
            'added' => [],
            'removed' => [],
            'modified' => [],
        ];
    }

    /**
     * Отменить версию - восстановить состояние до предыдущей версии и создать черновик для отмены
     */
    public function revertVersion(int $versionId, DraftService $draftService): array
    {
        return DB::transaction(function () use ($versionId, $draftService) {
            $version = ApprovedVersion::findOrFail($versionId);
            
            if (!$version->snapshot_before) {
                throw new \Exception('Невозможно отменить версию: отсутствует снимок состояния до изменений');
            }

            // Получаем текущее состояние (после версии)
            $currentSnapshot = $this->createSnapshot();
            
            // Получаем состояние до версии (которое нужно восстановить)
            $targetSnapshot = $version->snapshot_before;
            
            // Создаем черновик для отмены изменений
            $draftBatchId = \Illuminate\Support\Str::uuid()->toString();
            
            // Сравниваем текущее состояние с целевым и создаем черновики для восстановления
            $this->createRevertDrafts($currentSnapshot, $targetSnapshot, $draftBatchId, $draftService);
            
            // Восстанавливаем состояние из snapshot_before
            $this->restoreSnapshot($targetSnapshot);
            
            return [
                'success' => true,
                'message' => 'Версия отменена. Состояние восстановлено, создан черновик для отмены изменений.',
                'draft_batch_id' => $draftBatchId,
            ];
        });
    }

    /**
     * Создать черновики для отмены изменений
     */
    private function createRevertDrafts(array $currentSnapshot, array $targetSnapshot, string $draftBatchId, DraftService $draftService): void
    {
        // Создаем черновики для связей
        $currentRelations = collect($currentSnapshot['subject_competencies'] ?? []);
        $targetRelations = collect($targetSnapshot['subject_competencies'] ?? []);
        
        // Находим связи, которые нужно удалить (есть в текущем, но нет в целевом)
        $relationsToRemove = $currentRelations->filter(function ($current) use ($targetRelations) {
            return !$targetRelations->contains(function ($target) use ($current) {
                return $target['subject_type'] === $current['subject_type'] &&
                       $target['subject_id'] === $current['subject_id'] &&
                       $target['prof_competency_id'] === $current['prof_competency_id'];
            });
        });
        
        // Находим связи, которые нужно создать (есть в целевом, но нет в текущем)
        $relationsToCreate = $targetRelations->filter(function ($target) use ($currentRelations) {
            return !$currentRelations->contains(function ($current) use ($target) {
                return $current['subject_type'] === $target['subject_type'] &&
                       $current['subject_id'] === $target['subject_id'] &&
                       $current['prof_competency_id'] === $target['prof_competency_id'];
            });
        });
        
        // Создаем черновики для удаления связей
        foreach ($relationsToRemove as $relation) {
            $draftService->createSubjectCompetencyDraft([
                'draft_batch_id' => $draftBatchId,
                'original_subject_type' => $relation['subject_type'],
                'original_subject_id' => $relation['subject_id'],
                'original_prof_competency_id' => $relation['prof_competency_id'],
                'action' => 'remove',
                'comment' => 'Отмена версии - удаление связи',
            ]);
        }
        
        // Для создания связей используем action 'keep', так как create не поддерживается напрямую
        // Вместо этого создаем связи напрямую в restoreSnapshot
        
        // Создаем черновики для ДЕ
        $currentUnits = collect($currentSnapshot['didactic_units'] ?? []);
        $targetUnits = collect($targetSnapshot['didactic_units'] ?? []);
        
        // Находим ДЕ, которые нужно удалить
        $unitsToRemove = $currentUnits->filter(function ($current) use ($targetUnits) {
            return !$targetUnits->contains(function ($target) use ($current) {
                return $target['subject_type'] === $current['subject_type'] &&
                       $target['subject_id'] === $current['subject_id'] &&
                       $target['prof_competency_id'] === $current['prof_competency_id'] &&
                       $target['didactic_unit_id'] === $current['didactic_unit_id'];
            });
        });
        
        // Находим ДЕ, которые нужно создать
        $unitsToCreate = $targetUnits->filter(function ($target) use ($currentUnits) {
            return !$currentUnits->contains(function ($current) use ($target) {
                return $current['subject_type'] === $target['subject_type'] &&
                       $current['subject_id'] === $target['subject_id'] &&
                       $current['prof_competency_id'] === $target['prof_competency_id'] &&
                       $current['didactic_unit_id'] === $target['didactic_unit_id'];
            });
        });
        
        // Создаем черновики для удаления ДЕ
        foreach ($unitsToRemove as $unit) {
            $draftService->createDidacticUnitDraft([
                'draft_batch_id' => $draftBatchId,
                'subject_type' => $unit['subject_type'],
                'subject_id' => $unit['subject_id'],
                'prof_competency_id' => $unit['prof_competency_id'],
                'original_didactic_unit_id' => $unit['didactic_unit_id'],
                'action' => 'remove',
                'comment' => 'Отмена версии - удаление ДЕ',
            ]);
        }
        
        // Для создания ДЕ используем action 'keep', так как они будут восстановлены в restoreSnapshot
    }

    /**
     * Восстановить состояние системы из снимка
     */
    private function restoreSnapshot(array $snapshot): void
    {
        // Получаем текущее состояние
        $currentRelations = \App\Models\SubjectProfCompetency::all();
        $currentUnits = \App\Models\SubjectDidacticUnitProfCompetency::all();
        
        // Удаляем связи, которых нет в целевом снимке
        foreach ($currentRelations as $current) {
            $existsInTarget = collect($snapshot['subject_competencies'] ?? [])->contains(function ($target) use ($current) {
                return $target['subject_type'] === $current->subject_type &&
                       $target['subject_id'] === $current->subject_id &&
                       $target['prof_competency_id'] === $current->prof_competency_id;
            });
            
            if (!$existsInTarget) {
                $current->delete();
            }
        }
        
        // Создаем связи, которые есть в целевом снимке, но отсутствуют в текущем
        foreach ($snapshot['subject_competencies'] ?? [] as $target) {
            $exists = \App\Models\SubjectProfCompetency::where('subject_type', $target['subject_type'])
                ->where('subject_id', $target['subject_id'])
                ->where('prof_competency_id', $target['prof_competency_id'])
                ->exists();
            
            if (!$exists) {
                \App\Models\SubjectProfCompetency::create([
                    'subject_type' => $target['subject_type'],
                    'subject_id' => $target['subject_id'],
                    'prof_competency_id' => $target['prof_competency_id'],
                    'approved' => $target['approved'] ?? true,
                ]);
            }
        }
        
        // Удаляем связи ДЕ, которых нет в целевом снимке
        foreach ($currentUnits as $current) {
            $existsInTarget = collect($snapshot['didactic_units'] ?? [])->contains(function ($target) use ($current) {
                return $target['subject_type'] === $current->subject_type &&
                       $target['subject_id'] === $current->subject_id &&
                       $target['prof_competency_id'] === $current->prof_competency_id &&
                       $target['didactic_unit_id'] === $current->didactic_unit_id;
            });
            
            if (!$existsInTarget) {
                $current->delete();
            }
        }
        
        // Создаем связи ДЕ, которые есть в целевом снимке, но отсутствуют в текущем
        foreach ($snapshot['didactic_units'] ?? [] as $target) {
            $exists = \App\Models\SubjectDidacticUnitProfCompetency::where('subject_type', $target['subject_type'])
                ->where('subject_id', $target['subject_id'])
                ->where('prof_competency_id', $target['prof_competency_id'])
                ->where('didactic_unit_id', $target['didactic_unit_id'])
                ->exists();
            
            if (!$exists) {
                \App\Models\SubjectDidacticUnitProfCompetency::create([
                    'subject_type' => $target['subject_type'],
                    'subject_id' => $target['subject_id'],
                    'didactic_unit_id' => $target['didactic_unit_id'],
                    'prof_competency_id' => $target['prof_competency_id'],
                ]);
            }
        }
    }
}

