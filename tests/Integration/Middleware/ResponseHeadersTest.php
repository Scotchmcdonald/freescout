<?php

namespace Tests\Integration\Middleware;

use App\Http\Middleware\ResponseHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\IntegrationTestCase;

class ResponseHeadersTest extends IntegrationTestCase
{
    public function test_csp_headers_contain_cloudflare_insights()
    {
        $request = Request::create('/', 'GET');
        $middleware = new ResponseHeaders;

        $response = $middleware->handle($request, function ($req) {
            return new Response('Content');
        });

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com", $csp);
        $this->assertStringContainsString("connect-src 'self' ws: wss: https://cloudflareinsights.com", $csp);
    }
}
