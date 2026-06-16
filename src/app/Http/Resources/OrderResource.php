<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'seller_id' => $this->seller_id,
            'address_id' => $this->address_id,
            'wallet_id' => $this->wallet_id,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'batch_ref' => $this->batch_ref,
            'total_amount' => $this->total_amount,
            'cancel_reason' => $this->cancel_reason,
            'cancel_reason_notes' => $this->cancel_reason_notes,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'cancel_at' => $this->cancel_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
