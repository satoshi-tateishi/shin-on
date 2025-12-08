<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HasMasterOperations
{
    /**
     * 検索・フィルター機能
     */
    protected function applyFilters($query, Request $request)
    {
        // 名前での検索
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        // 有効/無効フィルター
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // ソート順
        $sortBy = $request->get('sort_by', 'sort');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, $this->getSortableColumns())) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            // デフォルトソート
            if (method_exists($query->getModel(), 'scopeOrdered')) {
                $query->ordered();
            } else {
                $query->orderBy('sort')->orderBy('name');
            }
        }

        return $query;
    }

    /**
     * 一括有効/無効切り替え
     */
    public function toggleActive(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_active' => 'required|boolean',
        ]);

        try {
            $modelClass = $this->getModelClass();
            $count = $modelClass::whereIn('id', $request->ids)
                ->update(['is_active' => $request->is_active]);

            $status = $request->is_active ? '有効' : '無効';

            return ApiResponse::success("{$count}件のデータを{$status}に変更しました。");

        } catch (\Exception $e) {
            return ApiResponse::serverError('一括更新に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 一括削除
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        try {
            $modelClass = $this->getModelClass();
            $count = $modelClass::whereIn('id', $request->ids)->delete();

            return ApiResponse::success("{$count}件のデータを削除しました。");

        } catch (\Exception $e) {
            return ApiResponse::serverError('一括削除に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * Ajax用データ取得
     */
    public function getData(Request $request): JsonResponse
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // フィルター適用
        $query = $this->applyFilters($query, $request);

        // ページネーション
        $perPage = min($request->get('per_page', 15), 100);
        $data = $query->paginate($perPage);

        return ApiResponse::data([
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * ソート可能なカラム一覧（各コントローラでオーバーライド）
     */
    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'created_at', 'updated_at'];
    }

    /**
     * 各コントローラで実装すべき抽象メソッド
     */
    abstract protected function getModelClass(): string;
}
