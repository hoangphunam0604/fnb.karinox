<?php

namespace App\Http\Resources\Api\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'fullname' => $this->fullname,
      'role' => $this->getRoleNames()->first(), // Lấy vai trò đầu tiên
      'permissions' => $this->getAllPermissions()->pluck('name'), // Lấy danh sách quyền hạn
      'current_branch' => $this->currentBranch ? new BranchResource($this->currentBranch) : null
    ];
  }
}
