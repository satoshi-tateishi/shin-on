<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subcategory_id' => 'required|exists:equipment_subcategories,id',
            'manufacturer' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'management_type' => 'required|in:individual,quantity',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|in:台,個,本,箱,ケース,ラック,セット',
            'model_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,in_use,repair,retired,lost',
            'location_id' => 'nullable|exists:locations,id',
            'now_location_id' => 'nullable|exists:locations,id',
            'is_discard' => 'nullable|boolean',
            'is_schedule_visible' => 'nullable|boolean',
            'discard_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'subcategory_id.required' => 'サブカテゴリは必須です。',
            'subcategory_id.exists' => '選択されたサブカテゴリが存在しません。',
            'name.required' => '機材名は必須です。',
            'name.max' => '機材名は255文字以内で入力してください。',
            'company_number.max' => '会社管理番号は50文字以内で入力してください。',
            'management_type.required' => '管理方式は必須です。',
            'management_type.in' => '正しい管理方式を選択してください。',
            'quantity.integer' => '数量は整数で入力してください。',
            'quantity.min' => '数量は1以上で入力してください。',
            'unit.in' => '正しい単位を選択してください。',
            'model_number.max' => '型番は100文字以内で入力してください。',
            'serial_number.max' => 'シリアル番号は100文字以内で入力してください。',
            'supplier.max' => '仕入先は255文字以内で入力してください。',
            'purchase_date.date' => '購入日は正しい日付形式で入力してください。',
            'warranty_expiry.date' => '保証期限は正しい日付形式で入力してください。',
            'price.numeric' => '価格は数値で入力してください。',
            'price.min' => '価格は0以上で入力してください。',
            'status.required' => '状態は必須です。',
            'status.in' => '正しい状態を選択してください。',
            'location_id.exists' => '選択された場所が存在しません。',
            'now_location_id.exists' => '選択された現在地が存在しません。',
            'discard_at.date' => '廃棄日は正しい日付形式で入力してください。',
            'notes.max' => '備考は1000文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'subcategory_id' => 'サブカテゴリ',
            'manufacturer' => 'メーカー',
            'name' => '機材名',
            'company_number' => '会社管理番号',
            'management_type' => '管理方式',
            'quantity' => '数量',
            'unit' => '単位',
            'model_number' => '型番',
            'serial_number' => 'シリアル番号',
            'supplier' => '仕入先',
            'purchase_date' => '購入日',
            'warranty_expiry' => '保証期限',
            'price' => '価格',
            'status' => '状態',
            'location_id' => '基本倉庫',
            'now_location_id' => '現在地',
            'is_discard' => '廃棄',
            'is_schedule_visible' => 'スケジュール表示',
            'discard_at' => '廃棄日',
            'notes' => '備考',
        ];
    }
}
