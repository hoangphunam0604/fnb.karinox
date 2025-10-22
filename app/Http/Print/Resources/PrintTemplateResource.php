<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrintTemplateResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */

  public function toArray($request)
  {
    return [
      'id'          => $this->id,
      'type'        => $this->type,
      'name'        => $this->name,
      'description' => $this->description,
      'content'     => $this->content,
      'is_default'  => $this->is_default,
      'is_active'   => $this->is_active,
      'updated_at'  => $this->updated_at->format("H:i d/m/Y"),
    ];
  }
}
