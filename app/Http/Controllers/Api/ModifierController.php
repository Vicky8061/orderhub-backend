<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModifierResource;
use App\Models\Modifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModifierController extends Controller
{
    /**
     * Display a listing of modifiers.
     */
    public function index(): AnonymousResourceCollection
    {
        $modifiers = Modifier::orderBy('name')->get();

        return ModifierResource::collection($modifiers);
    }

    /**
     * Store a newly created modifier.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price_adjustment' => ['required', 'numeric'],
        ]);

        $modifier = Modifier::create($validated);

        return response()->json([
            'message' => 'Modifier created successfully',
            'data' => new ModifierResource($modifier),
        ], 201);
    }

    /**
     * Display the specified modifier.
     */
    public function show(Modifier $modifier): ModifierResource
    {
        return new ModifierResource($modifier);
    }

    /**
     * Update the specified modifier.
     */
    public function update(Request $request, Modifier $modifier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'price_adjustment' => ['sometimes', 'numeric'],
        ]);

        $modifier->update($validated);

        return response()->json([
            'message' => 'Modifier updated successfully',
            'data' => new ModifierResource($modifier),
        ]);
    }

    /**
     * Remove the specified modifier.
     */
    public function destroy(Modifier $modifier): JsonResponse
    {
        $modifier->delete();

        return response()->json([
            'message' => 'Modifier deleted successfully',
        ]);
    }
}
