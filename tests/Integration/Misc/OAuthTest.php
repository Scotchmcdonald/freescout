<?php

declare(strict_types=1);

namespace Tests\Integration\Misc;

use App\Misc\OAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_authorization_url_for_microsoft(): void
    {
        $params = [
            'client_id' => 'test-client-id',
        ];

        $url = OAuth::getAuthorizationUrl(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('login.microsoftonline.com', $url);
        $this->assertStringContainsString('oauth2/v2.0/authorize', $url);
        $this->assertStringContainsString('test-client-id', $url);
    }

    public function test_get_authorization_url_includes_scopes(): void
    {
        $params = [
            'client_id' => 'test-client-id',
        ];

        $url = OAuth::getAuthorizationUrl(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertStringContainsString('scope=', $url);
        $this->assertStringContainsString('offline_access', urldecode($url));
        $this->assertStringContainsString('IMAP.AccessAsUser.All', urldecode($url));
        $this->assertStringContainsString('SMTP.Send', urldecode($url));
    }

    public function test_get_authorization_url_returns_empty_for_unknown_provider(): void
    {
        $url = OAuth::getAuthorizationUrl('unknown_provider', []);

        $this->assertEmpty($url);
    }

    public function test_get_access_token_microsoft_success(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'code' => 'test-code',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertEquals('test-access-token', $result['a_token']);
        $this->assertEquals('test-refresh-token', $result['r_token']);
        $this->assertEquals(3600, $result['expires_in']);
        $this->assertEquals(OAuth::PROVIDER_MICROSOFT, $result['provider']);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function test_get_access_token_microsoft_with_refresh_token(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'refresh_token' => 'old-refresh-token',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertEquals('new-access-token', $result['a_token']);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function test_get_access_token_handles_http_error(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'The provided authorization code or refresh token has expired.',
            ], 400),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'code' => 'invalid-code',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('400', $result['error']);
    }

    public function test_get_access_token_handles_network_exception(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => fn () => throw new \Exception('Network error'),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'code' => 'test-code',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Network error', $result['error']);
    }

    public function test_get_access_token_handles_missing_access_token_in_response(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'token_type' => 'Bearer',
                // Missing access_token
            ], 200),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'code' => 'test-code',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('No access token', $result['error']);
    }

    public function test_disconnect_microsoft_redirects(): void
    {
        $redirectUri = 'https://example.com/callback';

        $response = OAuth::disconnect(OAuth::PROVIDER_MICROSOFT, $redirectUri);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('login.microsoftonline.com', $targetUrl);
        $this->assertStringContainsString('logout', $targetUrl);
        $this->assertStringContainsString(urlencode($redirectUri), $targetUrl);
    }

    public function test_disconnect_unknown_provider_redirects_to_uri(): void
    {
        $redirectUri = 'https://example.com/fallback';

        $response = OAuth::disconnect('unknown', $redirectUri);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString($redirectUri, $response->getTargetUrl());
    }

    public function test_microsoft_smtp_constant(): void
    {
        $this->assertEquals('smtp.office365.com', OAuth::MICROSOFT_SMTP);
    }

    public function test_provider_microsoft_constant(): void
    {
        $this->assertEquals('ms', OAuth::PROVIDER_MICROSOFT);
    }

    public function test_get_access_token_sets_issued_on_timestamp(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'code' => 'test-code',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        $this->assertArrayHasKey('issued_on', $result);
        $this->assertNotEmpty($result['issued_on']);
    }

    public function test_get_access_token_preserves_old_refresh_token_if_not_returned(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                // No refresh_token in response
                'expires_in' => 3600,
            ], 200),
        ]);

        $params = [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'refresh_token' => 'old-refresh-token',
        ];

        $result = OAuth::getAccessToken(OAuth::PROVIDER_MICROSOFT, $params);

        // Should keep old refresh token
        $this->assertEquals('old-refresh-token', $result['r_token']);
    }
}
