<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * TRUSTED_PROXIES 環境変数でカンマ区切りIPリストを指定（例: 192.168.1.1,10.0.0.1）
     * '*' はすべてのプロキシを信頼（開発環境のみ）
     *
     * @var array<int,string>|string|null
     */
    protected $proxies = null;

    public function __construct()
    {
        $trusted = config('app.trusted_proxies');
        if ($trusted === '*') {
            $this->proxies = '*';
        } elseif ($trusted) {
            $this->proxies = array_map('trim', explode(',', $trusted));
        }
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
