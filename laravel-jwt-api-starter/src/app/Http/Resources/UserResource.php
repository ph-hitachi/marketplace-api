<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'user';
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user('api') ?: auth('api')->user() ?: $request->user();

        $allowed = $user && ($user->id === $this->id || $user->role === 'admin');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($allowed, $this->email),
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];
    }
}

