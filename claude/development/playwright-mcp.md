# Playwright MCP 設定ガイド

## 📌 概要
Playwright MCPは、Claude CodeからブラウザをプログラマティックAに制御するためのMCP（Model Context Protocol）サーバーです。Webページのナビゲーション、スクリーンショット撮影、要素の操作などを自動化できます。

## 🎯 主な機能
- ブラウザの自動操作（Chrome、Firefox、Safari、Edge対応）
- Webページのスクリーンショット撮影
- 要素のクリック、入力、フォーム送信
- ページナビゲーション（前へ、次へ）
- コンソールログ・ネットワークリクエストの取得
- タブ管理（新規、切替、閉じる）

## 🚀 セットアップ手順

### 1. Node.jsのインストール確認
```bash
node --version  # v18以上が必要
```

### 2. Playwright MCPサーバーのインストール
```bash
# グローバルインストール（推奨）
npm install -g @executeautomation/playwright-mcp-server

# インストール確認
npx @executeautomation/playwright-mcp-server --version
```

### 3. Claude Code MCP設定ファイルの編集

#### macOS/Linux
`~/.config/claude-code/mcp_settings.json` を編集

#### Windows
`%APPDATA%\claude-code\mcp_settings.json` を編集

### 4. 設定内容

```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": [
        "-y",
        "@executeautomation/playwright-mcp-server"
      ],
      "env": {
        "PLAYWRIGHT_BROWSER": "chromium",
        "PLAYWRIGHT_HEADLESS": "false",
        "PLAYWRIGHT_VIEWPORT_WIDTH": "1280",
        "PLAYWRIGHT_VIEWPORT_HEIGHT": "720"
      }
    }
  }
}
```

### 5. 環境変数の説明

| 変数名 | 説明 | デフォルト値 | 選択肢 |
|--------|------|------------|--------|
| `PLAYWRIGHT_BROWSER` | 使用するブラウザ | `chromium` | `chromium`, `firefox`, `webkit` (Safari), `msedge` |
| `PLAYWRIGHT_HEADLESS` | ヘッドレスモード | `false` | `true`, `false` |
| `PLAYWRIGHT_VIEWPORT_WIDTH` | ビューポート幅 | `1280` | 任意の数値（px） |
| `PLAYWRIGHT_VIEWPORT_HEIGHT` | ビューポート高さ | `720` | 任意の数値（px） |

### 6. ブラウザのインストール
```bash
# Chromiumをインストール
npx playwright install chromium

# すべてのブラウザをインストール
npx playwright install
```

### 7. Claude Codeの再起動
設定ファイルを編集した後、Claude Codeを完全に再起動してください。

```bash
# macOS/Linux
# Claude Codeを完全終了してから再起動

# または、設定をリロード
# Command/Ctrl + Shift + P → "Reload Window"
```

## 🔧 トラブルシューティング

### ブラウザが起動しない
```bash
# ブラウザの再インストール
npx playwright install --force chromium
```

### パーミッションエラー（macOS）
```bash
# System Preferences → Security & Privacy → Privacy タブ
# "Automation" セクションでClaude Codeを許可
```

### 動作確認
Claude Codeで以下のコマンドを実行して動作確認：
```
Yahoo Japanを開いてスクリーンショットを撮影して
```

成功すると `.playwright-mcp/` ディレクトリにスクリーンショットが保存されます。

## 📝 使用例

### 基本的な操作
```
# Webページを開く
https://example.com を開いて

# スクリーンショットを撮影
スクリーンショットを撮影して

# 要素をクリック
「ログイン」ボタンをクリックして

# テキストを入力
検索ボックスに「Laravel」と入力して

# フォームを送信
フォームを送信して
```

### 高度な操作
```
# 複数のタブを管理
新しいタブを開いて

# コンソールログを取得
コンソールログを表示して

# ネットワークリクエストを監視
ネットワークリクエストを表示して
```

## 🎨 カスタマイズ設定例

### Firefoxを使用する場合
```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["-y", "@executeautomation/playwright-mcp-server"],
      "env": {
        "PLAYWRIGHT_BROWSER": "firefox",
        "PLAYWRIGHT_HEADLESS": "false"
      }
    }
  }
}
```

### ヘッドレスモード（バックグラウンド実行）
```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["-y", "@executeautomation/playwright-mcp-server"],
      "env": {
        "PLAYWRIGHT_BROWSER": "chromium",
        "PLAYWRIGHT_HEADLESS": "true"
      }
    }
  }
}
```

### 高解像度スクリーンショット
```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["-y", "@executeautomation/playwright-mcp-server"],
      "env": {
        "PLAYWRIGHT_BROWSER": "chromium",
        "PLAYWRIGHT_HEADLESS": "false",
        "PLAYWRIGHT_VIEWPORT_WIDTH": "1920",
        "PLAYWRIGHT_VIEWPORT_HEIGHT": "1080"
      }
    }
  }
}
```

## 🔍 利用可能なMCPツール

Playwright MCPサーバーは以下のツールを提供します：

### ナビゲーション
- `mcp__playwright__browser_navigate` - URLに移動
- `mcp__playwright__browser_navigate_back` - 前のページに戻る
- `mcp__playwright__browser_close` - ブラウザを閉じる

### 操作
- `mcp__playwright__browser_click` - 要素をクリック
- `mcp__playwright__browser_type` - テキストを入力
- `mcp__playwright__browser_fill_form` - フォームに入力
- `mcp__playwright__browser_select_option` - ドロップダウンで選択
- `mcp__playwright__browser_press_key` - キーを押下

### 情報取得
- `mcp__playwright__browser_snapshot` - ページのスナップショット
- `mcp__playwright__browser_take_screenshot` - スクリーンショット撮影
- `mcp__playwright__browser_console_messages` - コンソールログ取得
- `mcp__playwright__browser_network_requests` - ネットワークリクエスト取得

### その他
- `mcp__playwright__browser_tabs` - タブ管理
- `mcp__playwright__browser_wait_for` - 要素やテキストを待機
- `mcp__playwright__browser_evaluate` - JavaScript実行

## 🔗 参考リンク

- [Playwright公式ドキュメント](https://playwright.dev/)
- [Playwright MCP Server GitHub](https://github.com/executeautomation/playwright-mcp-server)
- [MCP仕様](https://modelcontextprotocol.io/)

## ⚠️ 注意事項

1. **セキュリティ**: 信頼できないWebサイトでの自動操作には注意してください
2. **利用規約**: Webサイトの利用規約とrobots.txtを遵守してください
3. **レート制限**: 過度なリクエストは避けてください
4. **プライバシー**: 個人情報を含むページのスクリーンショットに注意してください

## 📊 パフォーマンス最適化

- ヘッドレスモードを使用するとリソース消費が少なくなります
- 不要な画像読み込みをブロックして高速化できます
- ビューポートサイズを小さくするとメモリ使用量が減ります

---

**最終更新**: 2025年10月10日
