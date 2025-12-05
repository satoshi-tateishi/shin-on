# フェーズ間機材継承機能仕様書

## 概要
出庫中の機材を別フェーズへ継承（移動）する機能。継承元の機材は自動返却され、継承先に予約済みとして登録される。

**実装状況**: 完了（2025年9月）

## ユースケース

```
リア王 本番（継承元）  →  ハリー・ポッター 本番（継承先）
  出庫中の機材           予約済みとして登録
  ↓ 自動返却
```

## 継承ルール

### 継承対象機材（継承元）
- **出庫中（checked_out）の機材のみ**が継承対象
- 予約済み・返却済みの機材は継承不可

### 継承先フェーズの条件
| 条件 | フィルタ |
|------|----------|
| 終了日 | `end_date >= today`（今日以降） |
| 自分自身 | 除外 |

### 継承先の分類
| 分類 | 説明 | 表示タイミング |
|------|------|----------------|
| 同一公演内 | 同じ公演の他フェーズ | 初期表示 |
| 他公演 | 異なる公演のフェーズ | 「表示」ボタンで遅延ロード |

### 継承時の自動処理
1. **継承先**: 「予約済み（reserved）」で新規作成
2. **継承元**: 「返却済み（checked_in）」に自動変更
3. **履歴記録**: `source_phase_equipment_id`に継承元を記録
4. **期間重複チェック**: 個体管理機材の重複を自動検出

## API エンドポイント

| メソッド | パス | 説明 |
|----------|------|------|
| GET | `/phases/{phase}/inheritable-target-phases` | 継承先フェーズ一覧 |
| GET | `/phases/{phase}/inheritance-preview` | 継承プレビュー |
| POST | `/phases/{phase}/inherit-to` | 継承実行 |

### パラメータ
```
# 継承先フェーズ一覧
?include_other_performances=true  # 他公演フェーズも取得

# 継承プレビュー
?target_phase_id=5  # 継承先フェーズID

# 継承実行（POST body）
{
  "target_phase_id": 5,
  "inherit_type": "all"  # all または selective
}
```

## 画面操作フロー

1. フェーズ機材管理画面で「継承」ボタンをクリック
2. 継承先フェーズを選択（同一公演/他公演）
3. 「次へ」で継承プレビュー表示
4. 「継承実行」で完了

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| コントローラー | `app/Http/Controllers/PhaseEquipmentInheritanceController.php` |
| ビュー | `resources/views/phase-equipment/index.blade.php` |
| モデル | `app/Models/PhaseEquipment.php` |
| ルート | `routes/web.php` |

---
**最終更新**: 2025年12月5日
