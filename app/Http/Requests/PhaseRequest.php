<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => 'nullable|exists:locations,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'フェーズ名は必須です。',
            'name.max' => 'フェーズ名は255文字以内で入力してください。',
            'start_date.required' => '開始日は必須です。',
            'start_date.date' => '開始日は有効な日付を入力してください。',
            'end_date.required' => '終了日は必須です。',
            'end_date.date' => '終了日は有効な日付を入力してください。',
            'end_date.after_or_equal' => '終了日は開始日以降の日付を入力してください。',
            'location_id.exists' => '選択された場所が存在しません。',
            'note.max' => '備考は1000文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'location_id' => '場所',
            'name' => 'フェーズ名',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'note' => '備考',
            'is_active' => '有効/無効',
        ];
    }
}