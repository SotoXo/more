<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tighten\Ziggy\Ziggy;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['trustedproxy.proxies' => '127.0.0.1', 'session.secure' => null]);
        Route::middleware('web')->get('/_qa/proxy', function () {
            session(['qa_proxy' => 'retained']);

            return response()->json([
                'login' => route('login'),
                'register' => route('register'),
                'password' => route('password.request'),
                'ziggy' => (new Ziggy)->toArray()['url'],
            ]);
        });
    }

    public function test_trusted_https_proxy_generates_https_routes_and_secure_session_cookie(): void
    {
        foreach (['first.example', 'changed.example'] as $host) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                ->withHeaders(['X-Forwarded-Proto' => 'https'])
                ->get('http://'.$host.'/_qa/proxy');

            $response->assertOk()->assertJson([
                'login' => 'https://'.$host.'/login',
                'register' => 'https://'.$host.'/register',
                'password' => 'https://'.$host.'/forgot-password',
                'ziggy' => 'https://'.$host,
            ])->assertSessionHas('qa_proxy', 'retained');
            $cookie = collect($response->headers->getCookies())
                ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
            $this->assertTrue($cookie->isSecure());
            $this->assertSame('lax', $cookie->getSameSite());
        }
    }

    public function test_untrusted_forwarded_proto_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/_qa/proxy')
            ->assertJsonPath('login', 'http://localhost/login');
    }

    public function test_local_http_remains_http(): void
    {
        $this->get('http://localhost/_qa/proxy')
            ->assertOk()->assertJsonPath('login', 'http://localhost/login');
    }
}
