# 🎯 JavaScript・フロントエンド開発標準

## 📏 JavaScript コーディング規約

### 基本規則
- **ES6+** 構文を積極的に使用
- **const/let** を使用、varは使用しない
- **アロー関数** を適切に使用
- **厳密等価演算子** (===, !==) を使用

### 命名規則
| 要素 | 規則 | 例 |
|------|------|-----|
| 変数・関数 | camelCase | `userName`, `getUserData()` |
| 定数 | SCREAMING_SNAKE_CASE | `API_ENDPOINT`, `MAX_RETRY_COUNT` |
| クラス | PascalCase | `UserController`, `DataProcessor` |
| ファイル名 | kebab-case | `user-dashboard.js`, `api-client.js` |

## 📅 **日付・時刻処理の重要ルール**

### ⚠️ タイムゾーン問題の回避
JavaScriptで日付を扱う際は、**必ずタイムゾーンを考慮**する：

```javascript
// 🚫 危険: toISOString()はUTC時間を返すため、タイムゾーンによっては前日になる
const badDate = new Date().toISOString().split('T')[0];
// 日本時間の深夜0時付近では "2025-09-25" のような前日になる可能性

// ✅ 推奨: ローカル時間ベースの日付取得
const goodDate = new Date().getFullYear() + '-' +
    String(new Date().getMonth() + 1).padStart(2, '0') + '-' +
    String(new Date().getDate()).padStart(2, '0');
// 常に現地時間での正しい日付 "2025-09-26"

// ✅ 代替案: toLocaleDateString()を使用
const alternativeDate = new Date().toLocaleDateString('sv-SE'); // "2025-09-26"
```

### 日付フォーマット関数
```javascript
// 推奨: 再利用可能な日付フォーマット関数
function formatDateLocal(date = new Date()) {
    return date.getFullYear() + '-' +
           String(date.getMonth() + 1).padStart(2, '0') + '-' +
           String(date.getDate()).padStart(2, '0');
}

// 使用例
const today = formatDateLocal(); // "2025-09-26"
const customDate = formatDateLocal(new Date('2025-12-25')); // "2025-12-25"
```

### Alpine.js での日付初期化
```javascript
// Alpine.js コンポーネントでの正しい日付設定
function inventoryDashboard() {
    return {
        // ✅ 正しい: ローカル時間ベースの初期化
        asOfDate: new Date().getFullYear() + '-' +
                  String(new Date().getMonth() + 1).padStart(2, '0') + '-' +
                  String(new Date().getDate()).padStart(2, '0'),

        // 📝 日付関連のメソッド
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'short'
            });
        }
    }
}
```

## 🎨 Alpine.js ベストプラクティス

### コンポーネント構造
```javascript
function componentName() {
    return {
        // 🔤 データプロパティ（アルファベット順）
        error: null,
        loading: false,
        userData: [],

        // 🚀 初期化メソッド
        async init() {
            try {
                await this.loadData();
            } catch (error) {
                this.error = error.message;
            }
        },

        // 📊 データ操作メソッド
        async loadData() {
            this.loading = true;
            try {
                // API呼び出しなど
            } finally {
                this.loading = false;
            }
        },

        // 🎯 UI操作メソッド
        openModal() {
            // モーダル表示ロジック
        }
    }
}
```

### エラーハンドリング
```javascript
// ✅ 適切なエラーハンドリング
async loadInventoryData() {
    this.loading = true;
    this.error = null;

    try {
        const response = await fetch('/api/inventory');
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'データの取得に失敗しました');
        }

        this.inventoryData = data.data;
    } catch (error) {
        console.error('Inventory loading error:', error);
        this.error = error.message;
    } finally {
        this.loading = false;
    }
}
```

## 🌐 API通信

### Fetch API使用パターン
```javascript
// ✅ 推奨: 統一されたAPI呼び出しパターン
async function apiCall(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    };

    const response = await fetch(url, { ...defaultOptions, ...options });
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || `HTTP error! status: ${response.status}`);
    }

    return data;
}

// 使用例
try {
    const result = await apiCall('/api/inventory', {
        method: 'POST',
        body: JSON.stringify({ date: '2025-09-26' })
    });
} catch (error) {
    console.error('API Error:', error);
}
```

## 🎭 DOM操作

### イベントハンドリング
```javascript
// ✅ Alpine.js でのイベント処理
<div x-data="componentName()">
    <!-- デバウンス付き入力 -->
    <input
        type="text"
        x-model="searchTerm"
        @input.debounce.500ms="performSearch()"
    >

    <!-- 条件付きボタン -->
    <button
        @click="submitForm()"
        :disabled="loading"
        :class="{ 'opacity-50': loading }"
    >
        <span x-show="!loading">送信</span>
        <span x-show="loading">送信中...</span>
    </button>
</div>
```

## ✅ JavaScript コード品質チェックリスト

### 基本項目
- [ ] const/let使用、var未使用
- [ ] 厳密等価演算子（===, !==）使用
- [ ] 適切なエラーハンドリング
- [ ] 非同期処理のtry-catch-finally
- [ ] **日付処理でタイムゾーン考慮**

### Alpine.js 固有項目
- [ ] コンポーネント初期化メソッド（init）実装
- [ ] ローディング状態管理
- [ ] エラー状態管理
- [ ] 適切なリアクティブデータ設計

### パフォーマンス項目
- [ ] デバウンス処理（検索など）
- [ ] 不要なAPI呼び出し防止
- [ ] メモリリーク対策
- [ ] DOM操作の最小化

## 🚨 よくある問題と対策

### タイムゾーン問題
```javascript
// 🚫 問題のあるコード
const date = new Date().toISOString().split('T')[0]; // UTC時間ベース

// ✅ 修正版
const date = new Date().getFullYear() + '-' +
             String(new Date().getMonth() + 1).padStart(2, '0') + '-' +
             String(new Date().getDate()).padStart(2, '0');
```

### 非同期処理の競合状態
```javascript
// 🚫 問題: 複数の非同期処理が競合
async loadData() {
    const data = await fetch('/api/data');
    this.data = await data.json();
}

// ✅ 修正: ローディング状態で制御
async loadData() {
    if (this.loading) return; // 既に処理中の場合は終了

    this.loading = true;
    try {
        const data = await fetch('/api/data');
        this.data = await data.json();
    } finally {
        this.loading = false;
    }
}
```

---
**[← README.md に戻る](../README.md)**