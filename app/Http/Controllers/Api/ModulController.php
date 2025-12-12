<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modul;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModulController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $moduls = Modul::with([
            'modulSubjects',
            'profCompetencies' => function($query) {
                $query->with(['modulSubjects', 'opSubjects']);
            }
        ])->get();
        return response()->json($moduls);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $modul = Modul::create($validated);
        return response()->json($modul, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $modul = Modul::with(['modulSubjects', 'profCompetencies'])->findOrFail($id);
        return response()->json($modul);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $modul = Modul::findOrFail($id);
        $modul->update($validated);
        return response()->json($modul);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $modul = Modul::findOrFail($id);
        $modul->delete();
        return response()->json(['message' => 'Модуль успешно удален'], 200);
    }
}
