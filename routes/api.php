<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ModulController;
use App\Http\Controllers\Api\ModulSubjectController;
use App\Http\Controllers\Api\OpSubjectController;
use App\Http\Controllers\Api\ProfCompetencyController;
use App\Http\Controllers\Api\DidacticUnitController;

// Модули
Route::apiResource('moduls', ModulController::class);
Route::get('moduls/{id}/subjects', [ModulController::class, 'getSubjects']);
Route::get('moduls/{id}/full-data', [ModulController::class, 'getFullData']);

// МДК (Междисциплинарные курсы)
Route::apiResource('modulsubjects', ModulSubjectController::class);
Route::put('modulsubjects/{id}/didactic-units', [ModulSubjectController::class, 'updateDidacticUnits']);
Route::get('modulsubjects/{id}/competencies', [ModulSubjectController::class, 'getCompetencies']);

// ОП (Общеобразовательные дисциплины)
Route::apiResource('op-subjects', OpSubjectController::class);
Route::put('op-subjects/{id}/didactic-units', [OpSubjectController::class, 'updateDidacticUnits']);
Route::get('op-subjects/{id}/competencies', [OpSubjectController::class, 'getCompetencies']);

// Профессиональные компетенции
Route::apiResource('prof-competencies', ProfCompetencyController::class);
Route::put('prof-competencies/{id}/modul-subjects', [ProfCompetencyController::class, 'updateModulSubjects']);
Route::put('prof-competencies/{id}/op-subjects', [ProfCompetencyController::class, 'updateOpSubjects']);
Route::post('prof-competencies/{id}/approve', [ProfCompetencyController::class, 'approveRelations']);
Route::post('prof-competencies/{id}/unapprove', [ProfCompetencyController::class, 'unapproveRelations']);

// Дидактические единицы
// Специфичные маршруты должны быть ПЕРЕД ресурсным маршрутом, чтобы избежать конфликтов
Route::get('didactic-units-table', [DidacticUnitController::class, 'table']);
Route::post('didactic-units/bulk-load-by-subjects', [DidacticUnitController::class, 'bulkLoadBySubjects']);
Route::get('didactic-units-duplicates', [DidacticUnitController::class, 'duplicates']);
Route::post('didactic-units/approve', [DidacticUnitController::class, 'approveDidacticUnits']);
Route::post('didactic-units/unapprove', [DidacticUnitController::class, 'unapproveDidacticUnits']);
Route::delete('didactic-units/delete-unused', [DidacticUnitController::class, 'deleteUnused']);
Route::apiResource('didactic-units', DidacticUnitController::class);

// Черновики и версионирование
use App\Http\Controllers\Api\DraftController;
Route::get('drafts', [DraftController::class, 'index']);
Route::get('drafts/find', [DraftController::class, 'findSubjectCompetencyDraft']);
Route::get('drafts/didactic-units-for-relation', [DraftController::class, 'getDidacticUnitDraftsForRelation']);
Route::post('drafts/subject-competency', [DraftController::class, 'createSubjectCompetencyDraft']);
Route::post('drafts/didactic-unit', [DraftController::class, 'createDidacticUnitDraft']);
Route::get('drafts/{draftBatchId}', [DraftController::class, 'show']);
Route::get('drafts/{draftBatchId}/preview', [DraftController::class, 'preview']);
Route::post('drafts/{draftBatchId}/apply', [DraftController::class, 'apply']);
Route::delete('drafts/{draftBatchId}', [DraftController::class, 'destroy']);

// Версии
use App\Http\Controllers\Api\VersionController;
Route::get('versions', [VersionController::class, 'index']);
Route::get('versions/{id}', [VersionController::class, 'show']);
Route::post('versions/compare', [VersionController::class, 'compare']);
Route::post('versions/{id}/revert', [VersionController::class, 'revert']);

