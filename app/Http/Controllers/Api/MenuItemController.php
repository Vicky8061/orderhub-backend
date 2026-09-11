<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Display a listing of menu items.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MenuItem::with(['category', 'modifiers']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->query('is_available'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->get();

        return MenuItemResource::collection($items);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'modifier_ids' => ['nullable', 'array'],
            'modifier_ids.*' => ['exists:modifiers,id'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_items', 'public');
        }

        $item = MenuItem::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'image_path' => $imagePath,
            'is_available' => $validated['is_available'] ?? true,
        ]);

        if (!empty($validated['modifier_ids'])) {
            $item->modifiers()->sync($validated['modifier_ids']);
        }

        $item->load(['category', 'modifiers']);

        return response()->json([
            'message' => 'Menu item created successfully',
            'data' => new MenuItemResource($item),
        ], 201);
    }

    /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem): MenuItemResource
    {
        $menuItem->load(['category', 'modifiers']);
        return new MenuItemResource($menuItem);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'modifier_ids' => ['nullable', 'array'],
            'modifier_ids.*' => ['exists:modifiers,id'],
        ]);

        if ($request->hasFile('image')) {
            if ($menuItem->image_path) {
                Storage::disk('public')->delete($menuItem->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('menu_items', 'public');
        }

        $menuItem->update($validated);

        if (array_key_exists('modifier_ids', $validated)) {
            $menuItem->modifiers()->sync($validated['modifier_ids'] ?? []);
        }

        $menuItem->load(['category', 'modifiers']);

        return response()->json([
            'message' => 'Menu item updated successfully',
            'data' => new MenuItemResource($menuItem),
        ]);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        if ($menuItem->image_path) {
            Storage::disk('public')->delete($menuItem->image_path);
        }

        $menuItem->delete();

        return response()->json([
            'message' => 'Menu item deleted successfully',
        ]);
    }
}
