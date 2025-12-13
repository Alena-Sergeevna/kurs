<?php

namespace App\Services;

use App\Models\ApprovedVersion;
use App\Models\SubjectProfCompetency;
use App\Models\SubjectDidacticUnitProfCompetency;
use Illuminate\Support\Facades\DB;

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
        $subjectCompetencies = SubjectProfCompetency::all()->map(function ($item) {
            return [
                'subject_type' => $item->subject_type,
                'subject_id' => $item->subject_id,
                'prof_competency_id' => $item->prof_competency_id,
                'approved' => $item->approved,
            ];
        });

        $didacticUnits = SubjectDidacticUnitProfCompetency::all()->map(function ($item) {
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
}

