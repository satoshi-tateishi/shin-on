<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class RolePermissionController extends Controller
{
    public function index()
    {
        // 現在のRole別権限設定を定義
        $permissions = $this->getPermissionMatrix();

        return view('admin.role-permissions.index', compact('permissions'));
    }

    /**
     * 権限マトリックスを取得
     */
    private function getPermissionMatrix(): array
    {
        return [
            'dashboard' => [
                'title' => 'ダッシュボード',
                'items' => [
                    ['name' => '機材管理', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '- 公演使用機材', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- 機材スケジュール表', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- 倉庫別 在庫表示', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- 倉庫別 在庫PDF出力', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- 修理管理', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- 倉庫間移動', 'general' => true, 'viewer' => false, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => 'マスタ管理', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '- 機材マスタ等8項目', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => 'システム管理', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '- 会社設定', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => '- バックアップ管理', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true, 'indent' => true],
                    ['name' => '- Role権限設定', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true, 'indent' => true],
                    ['name' => 'アクティビティ', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true],
                    ['name' => '- 操作履歴', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true, 'indent' => true],
                ],
            ],
            'equipment_management' => [
                'title' => '機材管理セクション',
                'items' => [
                    ['name' => '公演一覧 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '公演一覧 - 新規作成ボタン', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '公演詳細 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '公演詳細 - 編集ボタン', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '公演詳細 - フェーズ追加', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '公演詳細 - フェーズ編集', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ詳細 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ詳細 - 編集ボタン', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ詳細 - 削除ボタン', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ詳細 - PDF出力', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ機材一覧 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ機材一覧 - 機材追加', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ機材一覧 - 出庫/返却', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'フェーズ機材一覧 - 削除', 'general' => 'staff', 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '修理管理一覧 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '修理管理一覧 - 新規作成', 'general' => true, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '修理管理一覧 - 編集', 'general' => true, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '修理詳細 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '修理詳細 - 編集/ステータス変更', 'general' => true, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '修理詳細 - PDF出力', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                ],
            ],
            'master_management' => [
                'title' => 'マスタ管理セクション',
                'items' => [
                    ['name' => '機材マスタ一覧 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '機材マスタ一覧 - 新規作成', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '機材マスタ一覧 - 並び替え', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '機材マスタ一覧 - PDF出力', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '機材マスタ詳細 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '機材マスタ詳細 - 編集', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'カテゴリ/サブカテ一覧 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => 'カテゴリ/サブカテ一覧 - 新規作成', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'カテゴリ/サブカテ一覧 - 並び替え', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'カテゴリ/サブカテ一覧 - 行クリック→詳細', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => '機材セット詳細 - 編集', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '機材セット詳細 - 削除', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => '機材セット詳細 - 機材追加/削除', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'ユーザーマスタ一覧 - 新規作成', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true],
                    ['name' => 'ユーザー詳細 - 編集', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true],
                    ['name' => 'その他マスタ - 新規作成/編集', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
                    ['name' => 'その他マスタ - 行クリック→詳細', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
                    ['name' => 'CSVインポート/エクスポート', 'general' => false, 'viewer' => false, 'editor' => false, 'admin' => true],
                ],
            ],
        ];
    }
}
