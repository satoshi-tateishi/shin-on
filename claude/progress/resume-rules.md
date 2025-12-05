# 作業再開ルール

## セッション開始時

### 1. 進捗確認
```
claude/progress/implementation-status.md  # 現在の状況
claude/progress/work-log.md               # 前回の作業内容
```

### 2. 必要に応じて確認
```
claude/docs/Equipment_Management_System_Requirements_v2.md  # 要件
claude/docs/Equipment_Management_Database_Design_v2.md      # DB設計
```

### 3. 環境起動
```bash
./vendor/bin/sail up -d && ./vendor/bin/sail npm run start
```

---

## 作業完了時

### 必須
1. `work-log.md` に作業内容を簡潔に記録
2. `implementation-status.md` を必要に応じて更新

### 記録形式
```markdown
### MM/DD - 作業概要
- 完了した作業項目
- 作成・修正したファイル
```

---

## トラブルシューティング

```bash
# 環境リセット
./vendor/bin/sail down && ./vendor/bin/sail up -d

# キャッシュクリア
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# マイグレーション確認
./vendor/bin/sail artisan migrate:status
```

---

## 技術的注意点

| 項目 | 内容 |
|------|------|
| MySQL 8.0 | EXCLUDE制約未対応 |
| データ規模 | 5,000機材×100公演 |
| 同時利用 | 30ユーザー |
| 期間重複 | Laravelで実装 |

---

**最終更新**: 2025年9月28日
