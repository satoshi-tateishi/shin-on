#!/usr/bin/env node
/**
 * Laravel Boost MCP Proxy
 *
 * Claude Code の MCP タイムアウト対策として、Node.js が即座に起動し
 * `initialize` リクエストに素早く応答しながら、PHPプロセスを並行起動します。
 *
 * - Node.js プロセス起動: ~1ms
 * - initialize 応答: ~5ms (PHP起動を待たない)
 * - PHP (boost:mcp) 起動: ~1秒（バックグラウンドで並行実行）
 * - tools/list 等のリクエストは PHP 起動後に転送
 */

'use strict';

const { spawn } = require('child_process');
const readline = require('readline');

const CONTAINER = 'shin-on_db_app';

// PHP プロセスを即座にバックグラウンドで起動
const php = spawn('/usr/local/bin/docker', [
  'exec', '-i',
  '-e', 'XDEBUG_MODE=off',
  CONTAINER,
  'php', 'artisan', 'boost:mcp'
], { stdio: ['pipe', 'pipe', 'pipe'] });

let phpReady = false;
let phpInitializeResponseReceived = false;
const pendingMessages = [];
let phpBuffer = '';

// PHP が ready になったらバッファ済みメッセージを転送
function flushPending() {
  while (pendingMessages.length > 0) {
    const msg = pendingMessages.shift();
    php.stdin.write(msg + '\n');
  }
}

// PHP の stdout を Claude Code へ転送
// ただし最初の initialize レスポンスは Node.js が既に返したため破棄
php.stdout.on('data', (data) => {
  if (!phpInitializeResponseReceived) {
    // PHP の initialize レスポンスを検知して破棄
    phpBuffer += data.toString();
    const newlineIdx = phpBuffer.indexOf('\n');
    if (newlineIdx !== -1) {
      // 最初の行（initialize レスポンス）を捨てる
      phpBuffer = phpBuffer.slice(newlineIdx + 1);
      phpInitializeResponseReceived = true;
      phpReady = true;
      // 残りのバッファがあれば転送
      if (phpBuffer.length > 0) {
        process.stdout.write(phpBuffer);
        phpBuffer = '';
      }
      flushPending();
    }
    return;
  }
  process.stdout.write(data);
});

php.stderr.on('data', () => {
  // stderr は無視（Xdebug等の出力をフィルタ）
});

php.on('exit', (code) => {
  process.exit(code || 0);
});

php.on('error', () => {
  process.exit(1);
});

// Claude Code からの stdin を処理
const rl = readline.createInterface({ input: process.stdin });
let initializeSent = false;

rl.on('line', (line) => {
  const trimmed = line.trim();
  if (!trimmed) return;

  let parsed;
  try {
    parsed = JSON.parse(trimmed);
  } catch (e) {
    return;
  }

  // initialize リクエストには即時応答（PHPを待たずに）
  if (parsed.method === 'initialize' && !initializeSent) {
    initializeSent = true;
    const resp = {
      jsonrpc: '2.0',
      id: parsed.id,
      result: {
        protocolVersion: '2024-11-05',
        capabilities: {
          tools: { listChanged: false },
          resources: { listChanged: false },
          prompts: { listChanged: false }
        },
        serverInfo: { name: 'Laravel Boost', version: '0.0.1' },
        instructions: 'Laravel ecosystem MCP server offering database schema access, Artisan commands, error logs, Tinker execution, semantic documentation search and more. Boost helps with code generation.'
      }
    };
    process.stdout.write(JSON.stringify(resp) + '\n');
    // PHP にも initialize を転送（PHP のセッション確立のため）
    // PHP のレスポンスは stdout ハンドラで破棄する
    php.stdin.write(trimmed + '\n');
    return;
  }

  // notifications/initialized は PHP にも転送
  if (parsed.method === 'notifications/initialized') {
    if (phpReady) {
      php.stdin.write(trimmed + '\n');
    } else {
      pendingMessages.push(trimmed);
    }
    return;
  }

  // その他のリクエストは PHP が ready になったら転送
  if (phpReady) {
    php.stdin.write(trimmed + '\n');
  } else {
    pendingMessages.push(trimmed);
  }
});

rl.on('close', () => {
  php.stdin.end();
});

process.on('SIGTERM', () => {
  php.kill('SIGTERM');
  process.exit(0);
});
