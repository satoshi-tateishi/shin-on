<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sort' => $this->sort,
            'type' => $this->type,
            'name' => $this->name,
            'furigana' => $this->furigana,
            'tel1_name' => $this->tel1_name,
            'tel1' => $this->tel1,
            'tel2_name' => $this->tel2_name,
            'tel2' => $this->tel2,
            'fax' => $this->fax,
            'email1_name' => $this->email1_name,
            'email1' => $this->email1,
            'email2_name' => $this->email2_name,
            'email2' => $this->email2,
            'postal_code' => $this->postal_code,
            'address' => $this->address,
            'note' => $this->note,
            'is_active' => $this->is_active,
            'is_inventory_visible' => $this->is_inventory_visible,
            'is_transfer_visible' => $this->is_transfer_visible,
            'is_main_warehouse' => $this->is_main_warehouse,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
