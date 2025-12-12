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

// МДК (Междисциплинарные курсы)
Route::apiResource('modulsubjects', ModulSubjectController::class);
Route::put('modulsubjects/{id}/didactic-units', [ModulSubjectController::class, 'updateDidacticUnits']);

// ОП (Общеобразовательные дисциплины)
Route::apiResource('op-subjects', OpSubjectController::class);
Route::put('op-subjects/{id}/didactic-units', [OpSubjectController::class, 'updateDidacticUnits']);

// Профессиональные компетенции
Route::apiResource('prof-competencies', ProfCompetencyController::class);
Route::put('prof-competencies/{id}/modul-subjects', [ProfCompetencyController::class, 'updateModulSubjects']);
Route::put('prof-competencies/{id}/op-subjects', [ProfCompetencyController::class, 'updateOpSubjects']);
Route::post('prof-competencies/{id}/approve', [ProfCompetencyController::class, 'approveRelations']);
Route::post('prof-competencies/{id}/unapprove', [ProfCompetencyController::class, 'unapproveRelations']);

// Дидактические единицы
Route::apiResource('didactic-units', DidacticUnitController::class);
Route::get('didactic-units-table', [DidacticUnitController::class, 'table']);

