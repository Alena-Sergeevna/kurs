<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovedVersion;
use App\Services\VersionService;
use App\Services\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(
        protected VersionService $versionService,
        protected DraftService $draftService
    ) {}

    /**
     * Получить список всех версий
     */
    public function index(Request $request): JsonResponse
    {
        $versions = ApprovedVersion::orderBy('version_number', 'desc')
            ->get()
            ->map(function ($version) {
                $summary = $version->changes_summary;
                if (is_string($summary)) {
                    $summary = json_decode($summary, true);
                }

                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'approved_at' => $version->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $version->approved_by,
                    'changes_summary' => $summary,
                    'draft_batch_id' => $version->draft_batch_id,
                    'created_at' => $version->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($versions);
    }

    /**
     * Получить детали версии
     */
    public function show(int $id): JsonResponse
    {
        $version = ApprovedVersion::findOrFail($id);
        
        $summary = $version->changes_summary;
        if (is_string($summary)) {
            $summary = json_decode($summary, true);
        }

        return response()->json([
            'id' => $version->id,
            'version_number' => $version->version_number,
            'approved_at' => $version->approved_at?->format('Y-m-d H:i:s'),
            'approved_by' => $version->approved_by,
            'changes_summary' => $summary,
            'draft_batch_id' => $version->draft_batch_id,
            'snapshot_before' => $version->snapshot_before,
            'snapshot_after' => $version->snapshot_after,
            'created_at' => $version->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Сравнить две версии
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version1_id' => 'required|integer|exists:approved_versions,id',
            'version2_id' => 'required|integer|exists:approved_versions,id',
        ]);

        $comparison = $this->versionService->compareVersions(
            $validated['version1_id'],
            $validated['version2_id']
        );

        return response()->json($comparison);
    }

    /**
     * Отменить версию - восстановить состояние до предыдущей версии
     */
    public function revert(int $id): JsonResponse
    {
        try {
            $result = $this->versionService->revertVersion($id, $this->draftService);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'draft_batch_id' => $result['draft_batch_id'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка отмены версии: ' . $e->getMessage(),
            ], 422);
        }
    }
}

