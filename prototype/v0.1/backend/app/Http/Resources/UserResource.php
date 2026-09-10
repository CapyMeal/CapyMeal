<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => ! is_null($this->email_verified_at),
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'two_factor_recovery_codes_remaining' => count($this->two_factor_recovery_codes ?? []),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
        ];
    }
}
