<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComboResource;
use App\Models\Combo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComboController extends Controller
{
    /**
     * Display a listing of combos.
     */
    public function index(): AnonymousResourceCollection
    {
        $combos = Combo::with('menuItems')->orderBy('name')->get();

        return ComboResource::collection($combos);
    }

    /**
     * Store a newly created combo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $combo = Combo::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'is_available' => $validated['is_available'] ?? true,
        ]);

        $attachData = [];
        foreach ($validated['items'] as $item) {
            $attachData[$item['menu_item_id']] = [
                'quantity' => $item['quantity'] ?? 1,
            ];
        }
        $combo->menuItems()->attach($attachData);

        $combo->load('menuItems');

        return response()->json([
            'message' => 'Combo created successfully',
            'data' => new ComboResource($combo),
        ], 201);
    }

    /**
     * Display the specified combo.
     */
    public function show(Combo $combo): ComboResource
    {
        $combo->load('menuItems');
        return new ComboResource($combo);
    }

    /**
     * Update the specified combo.
     */
    public function update(Request $request, Combo $combo): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $combo->update($validated);

        if (array_key_exists('items', $validated)) {
            $syncData = [];
            foreach ($validated['items'] as $item) {
                $syncData[$item['menu_item_id']] = [
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }
            $combo->menuItems()->sync($syncData);
        }

        $combo->load('menuItems');

        return response()->json([
            'message' => 'Combo updated successfully',
            'data' => new ComboResource($combo),
        ]);
    }

    /**
     * Remove the specified combo.
     */
    public function destroy(Combo $combo): JsonResponse
    {
        $combo->delete();

        return response()->json([
            'message' => 'Combo deleted successfully',
        ]);
    }
}
