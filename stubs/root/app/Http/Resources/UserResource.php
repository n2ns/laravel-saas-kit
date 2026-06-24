<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'email_verified' => $this->email_verified_at !== null,
            'plan' => $this->getPlan(),
            'status' => $this->getStatus(),
            'auth_epoch' => $this->auth_epoch ?? 1,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
