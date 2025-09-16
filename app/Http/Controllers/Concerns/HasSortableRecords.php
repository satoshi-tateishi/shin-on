<?php

namespace App\Http\Controllers\Concerns;

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

            return response()->json([
                'success' => true,
                'message' => 'ソート順を更新しました。',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ソート順の更新に失敗しました: '.$e->getMessage(),
            ], 500);
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
        return $this->getMaxSortOrder() + 10;
    }

    /**
     * 各コントローラで実装すべき抽象メソッド
     */
    abstract protected function getModelClass(): string;
}
