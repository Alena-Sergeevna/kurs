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
Route::apiResource('didactic-units', DidacticUnitController::class);
Route::get('didactic-units-table', [DidacticUnitController::class, 'table']);
Route::post('didactic-units/bulk-load-by-subjects', [DidacticUnitController::class, 'bulkLoadBySubjects']);
Route::get('didactic-units-duplicates', [DidacticUnitController::class, 'duplicates']);

