# Laravel Boost MCP セットアップガイド

> 作成日: 2026-03-21
> 対象環境: Laravel Sail (Docker) + Claude Code CLI v2.1.81

---

## 概要

Laravel Boost は Laravel 公式の AI 開発支援 MCP サーバーです。
本ドキュメントでは、**Docker (Laravel Sail) 環境での接続に失敗する問題**の根本原因と解決策を記録します。

---

## 構成ファイル

| ファイル | 役割 |
|---|---|
| `.mcp.json` | プロジェクトレベルの MCP サーバー設定 |
| `claude/boost-mcp-proxy.cjs` | Node.js プロキシ（現行） |

---

## 問題の経緯

### 当初の試み

`.mcp.json` に直接 `docker exec` コマンドを設定：

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "/usr/local/bin/docker",
      "args": ["exec", "-i", "-e", "XDEBUG_MODE=off", "shin-on_db_app", "php", "artisan", "boost:mcp"]
    }
  }
}
```

**結果**: `✗ Failed to connect`

---

## 根本原因の調査

### 検証1: MCP サーバー自体の動作確認

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"initialize",...}' \
  | docker exec -i -e XDEBUG_MODE=off shin-on_db_app php artisan boost:mcp
```

→ **正常に JSON レスポンスが返る** → MCP プロトコルは問題なし

### 検証2: stdout バッファリングの確認

```python
# fwrite(STDOUT) の後に fflush なしでもデータが届くか
proc = subprocess.Popen(['docker', 'exec', '-i', ...], ...)
proc.stdin.write(msg); proc.stdin.flush()
select.select([proc.stdout], [], [], 5)  # → 応答あり
```

→ **バッファリング問題ではない**（PHP の fwrite は即時フラッシュ）

### 検証3: 環境変数・Xdebug 警告

```bash
docker exec shin-on_db_app env | grep XDEBUG
# → XDEBUG_MODE=false  ← コンテナ内に設定済み
```

`-e XDEBUG_MODE=off` を渡すことで警告を抑制済み → **問題なし**

### 検証4: 起動時間の計測（根本原因特定）

```python
t0 = time.time()
proc = subprocess.Popen(['docker', 'exec', ..., 'php', 'artisan', 'boost:mcp'], ...)
proc.stdin.write(initialize_msg); proc.stdin.flush()
select.select([proc.stdout], [], [], 10)
print(f'応答時間: {time.time()-t0:.3f}s')
# → 約 1.1〜1.2 秒
```

### 検証5: タイムアウト上限の確認

即時応答する Node.js テストサーバーで比較：

```javascript
// Node.js で即座にレスポンスを返すだけのサーバー
process.stdout.write(JSON.stringify(initResponse) + '\n');
```

```
test-instant: node /tmp/test_mcp.js  - ✓ Connected   (< 100ms)
laravel-boost: docker exec ... php artisan boost:mcp  - ✗ Failed to connect  (~1.1s)
```

**結論: Claude Code の MCP 初期化タイムアウトは約 1 秒未満。PHP/Laravel の起動（~1.1秒）がこれを超えていた。**

---

## 解決策: Node.js プロキシパターン

### アーキテクチャ

```
Claude Code
    │ stdin/stdout
    ▼
boost-mcp-proxy.cjs  (Node.js: 起動 ~1ms)
    │
    ├── initialize リクエスト受信
    │       └── 即時応答（ハードコード, ~5ms）← タイムアウト回避
    │           ＆ PHP へも転送（セッション確立のため）
    │
    ├── PHP プロセスを並行起動（バックグラウンド）
    │       docker exec -i -e XDEBUG_MODE=off shin-on_db_app php artisan boost:mcp
    │
    ├── PHP の initialize レスポンスを受信 → phpReady = true
    │       （このレスポンスは Node.js が既に返済のため破棄）
    │
    └── 以降のリクエスト（tools/list 等）を PHP へ転送
```

### タイムライン

```
t=0ms    : Claude Code が boost-mcp-proxy.cjs を起動
t=1ms    : Node.js 起動完了
t=5ms    : Claude Code が initialize 送信
t=10ms   : Node.js が initialize に即時応答 ✓ ← Claude Code が接続成功と判定
t=1100ms : PHP/Laravel 起動完了
t=1150ms : tools/list 等のリクエストが PHP に転送され処理される
```

---

## 実装: boost-mcp-proxy.cjs

`claude/boost-mcp-proxy.cjs` の主要ロジック：

```javascript
// 1. PHP プロセスをすぐにバックグラウンド起動
const php = spawn('/usr/local/bin/docker', [
  'exec', '-i', '-e', 'XDEBUG_MODE=off', 'shin-on_db_app',
  'php', 'artisan', 'boost:mcp'
], { stdio: ['pipe', 'pipe', 'pipe'] });

// 2. PHP の最初の応答（initialize レスポンス）を検知して破棄し phpReady = true
php.stdout.on('data', (data) => {
  if (!phpInitializeResponseReceived) {
    // 最初の行（PHP の initialize レスポンス）を捨てる
    phpInitializeResponseReceived = true;
    phpReady = true;
    flushPending();  // バッファ済みメッセージを転送
    return;
  }
  process.stdout.write(data);  // 以降は素通し
});

// 3. initialize には即時応答
if (parsed.method === 'initialize') {
  process.stdout.write(JSON.stringify(hardcodedResponse) + '\n');
  php.stdin.write(trimmed + '\n');  // PHP にも転送
  return;
}

// 4. その他は PHP が ready になったら転送（未ready ならバッファ）
if (phpReady) {
  php.stdin.write(trimmed + '\n');
} else {
  pendingMessages.push(trimmed);
}
```

### 注意: package.json の `"type": "module"` 問題

このプロジェクトの `package.json` には `"type": "module"` が設定されているため、
`.js` 拡張子のファイルは ES module として扱われ `require()` が使えない。

**解決策**: ファイル名を `.cjs` にすることで CommonJS として扱われる。

```
❌ boost-mcp-proxy.js  → ReferenceError: require is not defined
✅ boost-mcp-proxy.cjs → 正常動作
```

---

## .mcp.json 設定

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "node",
      "args": [
        "/Users/satoshi/Laravel/shin-on_db/claude/boost-mcp-proxy.cjs"
      ]
    }
  }
}
```

---

## ユーザーレベル設定（~/.claude.json）

project-level `.mcp.json` に加えて、グローバル設定にも登録済み：

```bash
claude mcp add -s user -t stdio laravel-boost \
  node /Users/satoshi/Laravel/shin-on_db/claude/boost-mcp-proxy.cjs
```

---

## Docker コンテナが停止している場合

現行の `boost-mcp-proxy.cjs` はコンテナが停止していると接続に失敗します。
その場合は先にコンテナを起動してください：

```bash
./vendor/bin/sail up -d
```

コンテナ自動起動が必要な場合は、`boost-mcp-proxy.cjs` の先頭で `docker ps` を確認し
`sail up -d` を呼び出すロジックを追加することで対応できます。

---

## 動作確認コマンド

```bash
# MCP サーバーの接続状態を確認
claude mcp list

# 期待される出力:
# laravel-boost: node .../boost-mcp-proxy.cjs - ✓ Connected

# 手動テスト（プロキシ経由で tools/list を取得）
(
  echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1"}}}'
  sleep 0.1
  echo '{"jsonrpc":"2.0","method":"notifications/initialized","params":{}}'
  sleep 2
  echo '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
  sleep 1
) | node claude/boost-mcp-proxy.cjs
```

---

## 試みた解決策の一覧

| 方法 | 結果 | 理由 |
|---|---|---|
| `.mcp.json` に bash ラッパースクリプト | ❌ Failed | PHP 起動が ~1s でタイムアウト |
| `.mcp.json` に `docker exec` 直接 | ❌ Failed | 同上 |
| `claude mcp add -s user` でユーザーレベル登録 | ❌ Failed | 同上（スコープ問題ではなかった） |
| Node.js プロキシ（即時 initialize 応答） | ✅ Connected | ~10ms で応答、PHP は並行起動 |

---

## 関連情報

- Laravel Boost 公式: https://laravel.com/docs/12.x/boost
- GitHub Issues (project-level .mcp.json 再接続バグ): https://github.com/anthropics/claude-code/issues/16107
- MCP プロトコル仕様: stdio transport では initialize → notifications/initialized → tool calls の順序
