<?php

namespace App\Http\Requests;

use App\Models\ReservationSlot;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // auth 必須
    }

    public function rules(): array
    {
        $rules = [
            'slot_id'    => ['required','exists:reservation_slots,id'],
            'product_id' => ['required','exists:products,id'],
            'quantity'   => ['required','integer','min:1'],
            'notes'      => ['nullable','string','max:1000'],
        ];

        // slot_type に応じて配達先を必須化
        $slot = ReservationSlot::find($this->input('slot_id'));
        if ($slot && $slot->slot_type === 'delivery') {
            $rules += [
                'delivery_area'        => ['required','in:namie,futaba,okuma,odaka'],
                'delivery_postal_code' => ['required','regex:/^[0-9]{3}-?[0-9]{4}$/'],
                'delivery_address'     => ['required','string','max:255'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'delivery_area.required' => '配達エリアを選択してください。',
        ];
    }
}
