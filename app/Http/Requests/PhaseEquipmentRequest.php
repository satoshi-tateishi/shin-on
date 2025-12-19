<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhaseEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => 'required|exists:equipments,id',
            'quantity' => 'required|integer|min:1|max:999',
            'note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => '機材を選択してください。',
            'equipment_id.exists' => '選択された機材が存在しません。',
            'quantity.required' => '数量は必須です。',
            'quantity.integer' => '数量は整数で入力してください。',
            'quantity.min' => '数量は1以上で入力してください。',
            'quantity.max' => '数量は999以下で入力してください。',
            'note.max' => '備考は1000文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'equipment_id' => '機材',
            'quantity' => '数量',
            'note' => '備考',
        ];
    }
}
