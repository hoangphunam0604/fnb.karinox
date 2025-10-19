<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintTemplateResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'type' => $this->type,
      'content' => $this->content,
      'variables' => $this->variables,
      'is_active' => $this->is_active,
      'branch_id' => $this->branch_id,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'branch' => $this->whenLoaded('branch', function () {
        return [
          'id' => $this->branch->id,
          'name' => $this->branch->name,
        ];
      }),
    ];
  }
}
