<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
        public function rules(): array
    {
        return [
            /**
             * Integer corresponding to CancelReason enum values.
             */
            'cancel_reason'       => ['required', 'integer', \Illuminate\Validation\Rule::enum(\App\Enums\CancelReason::class)],

            /**
             * Required if cancel_reason is 5 (Other). Additional cancel details.
             */
            'cancel_reason_notes' => ['required_if:cancel_reason,5', 'nullable', 'string', 'max:255'],
        ];
    }
}
