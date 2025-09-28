<?php

namespace App\Http\Requests;

use App\Enums\PerformanceStatus;
use App\Enums\PerformanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $performanceTypes = array_column(PerformanceType::cases(), 'value');
        $statuses = array_column(PerformanceStatus::cases(), 'value');

        return [
            'title' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'performance_type' => [
                'required',
                Rule::in($performanceTypes)
            ],
            'director' => 'nullable|string|max:255',
            'status' => [
                'required',
                Rule::in($statuses)
            ],
            'note' => 'nullable|string',
            'is_active' => 'boolean',
            'production_ids' => 'nullable|array',
            'production_ids.*' => 'exists:productions,id',
            'staff' => 'nullable|array',
            'staff.*.user_id' => 'nullable|exists:users,id',
            'staff.*.position_id' => 'nullable|exists:positions,id',
            'sound_designers' => 'nullable|array',
            'sound_designers.*' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '公演名は必須です。',
            'title.max' => '公演名は255文字以内で入力してください。',
            'short_name.max' => '略称は50文字以内で入力してください。',
            'performance_type.required' => '公演種別を選択してください。',
            'performance_type.in' => '有効な公演種別を選択してください。',
            'director.max' => '演出者名は255文字以内で入力してください。',
            'status.required' => 'ステータスを選択してください。',
            'status.in' => '有効なステータスを選択してください。',
            'production_ids.*.exists' => '選択されたプロダクションが存在しません。',
            'staff.*.user_id.exists' => '選択されたスタッフが存在しません。',
            'staff.*.position_id.exists' => '選択されたポジションが存在しません。',
            'sound_designers.*.exists' => '選択されたサウンドデザイナーが存在しません。',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '公演名',
            'short_name' => '略称',
            'performance_type' => '公演種別',
            'director' => '演出',
            'status' => 'ステータス',
            'note' => '備考',
            'production_ids' => 'プロダクション',
            'staff' => 'スタッフ',
            'sound_designers' => 'サウンドデザイナー',
        ];
    }
}