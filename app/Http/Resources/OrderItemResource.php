<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $itemName = $this->combo_id
            ? ($this->combo?->name ?? 'Combo')
            : ($this->menuItem?->name ?? 'Item');

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'menu_item_id' => $this->menu_item_id,
            'combo_id' => $this->combo_id,
            'item_name' => $itemName,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'notes' => $this->notes,
            'modifiers' => OrderItemModifierResource::collection($this->whenLoaded('modifiers')),
        ];
    }
}
