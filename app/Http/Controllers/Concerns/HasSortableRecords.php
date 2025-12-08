<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HasSortableRecords
{
    /**
     * Ajax ソート更新
     */
    public function updateSort(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.sort' => 'required|integer',
        ]);

        try {
            $modelClass = $this->getModelClass();

            foreach ($request->items as $item) {
                $modelClass::where('id', $item['id'])
                    ->update(['sort' => $item['sort']]);
            }

            return ApiResponse::success('ソート順を更新しました。');

        } catch (\Exception $e) {
            return ApiResponse::serverError('ソート順の更新に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * ソート順の最大値を取得
     */
    protected function getMaxSortOrder(): int
    {
        $modelClass = $this->getModelClass();

        return $modelClass::max('sort') ?? 0;
    }

    /**
     * 新規レコードのソート順を設定
     */
    protected function getNextSortOrder(): int
    {
        return $this->getMaxSortOrder() + 1;
    }

    /**
     * 各コントローラで実装すべき抽象メソッド
     */
    abstract protected function getModelClass(): string;
}
