<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\FrameGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\PureUnitTestCase;

class FrameGuardTest extends PureUnitTestCase
{
    private FrameGuard $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new FrameGuard;
    }

    public function test_handle_sets_x_frame_options_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Test content');
        });

        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function test_handle_sets_content_security_policy_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Test content');
        });

        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_handle_allows_request_to_continue(): void
    {
        $request = Request::create('/test', 'GET');
        $expectedContent = 'Test response content';

        $response = $this->middleware->handle($request, function ($req) use ($expectedContent) {
            return new Response($expectedContent);
        });

        $this->assertEquals($expectedContent, $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_handle_appends_to_existing_csp_header(): void
    {
        $request = Request::create('/test', 'GET');
        $existingCsp = "default-src 'self'";

        $response = $this->middleware->handle($request, function ($req) use ($existingCsp) {
            $response = new Response('Test content');
            $response->headers->set('Content-Security-Policy', $existingCsp);

            return $response;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString($existingCsp, $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_handle_does_not_duplicate_frame_ancestors_directive(): void
    {
        $request = Request::create('/test', 'GET');
        $existingCsp = "default-src 'self'; frame-ancestors 'self'";

        $response = $this->middleware->handle($request, function ($req) use ($existingCsp) {
            $response = new Response('Test content');
            $response->headers->set('Content-Security-Policy', $existingCsp);

            return $response;
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertEquals($existingCsp, $csp);
    }

    public function test_handle_preserves_response_status_code(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Not Found', 404);
        });

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Frame-Options'));
    }

    public function test_handle_works_with_json_responses(): void
    {
        $request = Request::create('/api/test', 'GET');
        $jsonData = ['status' => 'success', 'data' => ['id' => 1]];

        $response = $this->middleware->handle($request, function ($req) use ($jsonData) {
            return new JsonResponse($jsonData);
        });

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertJson($response->getContent());
    }
}
