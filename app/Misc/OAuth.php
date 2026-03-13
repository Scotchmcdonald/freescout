<?php

declare(strict_types=1);

namespace App\Misc;

use Illuminate\Support\Facades\Http;

class OAuth
{
    const PROVIDER_MICROSOFT = 'ms';
    const MICROSOFT_SMTP = 'smtp.office365.com';

    /**
     * Get authorization URL.
     *
     * @param  array<string, mixed>  $params
     */
    public static function getAuthorizationUrl(string $provider_code, array $params): string
    {
        $url = '';
        $args = [];

        switch ($provider_code) {
            case self::PROVIDER_MICROSOFT:
                // https://docs.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth
                $args = [
                    'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
                    'response_type' => 'code',
                    'approval_prompt' => 'auto',
                    'redirect_uri' => route('mailboxes.oauth_callback'),
                ];
                $args = array_merge($args, $params);
                $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($args);
                break;
        }

        return $url;
    }

    /**
     * Get access token.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function getAccessToken(string $provider_code, array $params): array
    {
        $token_data = [];
        $post_params = [];

        switch ($provider_code) {
            case self::PROVIDER_MICROSOFT:
                $post_params = [
                    'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => route('mailboxes.oauth_callback'),
                ];

                $post_params = array_merge($post_params, $params);

                // Refreshing Access Token.
                if (! empty($post_params['refresh_token'])) {
                    $post_params['grant_type'] = 'refresh_token';
                }

                $full_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

                try {
                    $response = Http::asForm()->post($full_url, $post_params);

                    if ($response->successful()) {
                        $result = $response->json();

                        if (is_array($result) && ! empty($result['access_token'])) {
                            $token_data['provider'] = self::PROVIDER_MICROSOFT;
                            $token_data['a_token'] = $result['access_token'];
                            $token_data['r_token'] = $result['refresh_token'] ?? ($params['refresh_token'] ?? null); // Keep old refresh token if not returned? Usually it returns a new one.
                            $token_data['issued_on'] = now()->toDateTimeString();
                            $token_data['expires_in'] = $result['expires_in'] ?? 0;
                        } else {
                            $token_data['error'] = 'No access token in response';
                        }
                    } else {
                        $token_data['error'] = 'Response code: '.$response->status().' Body: '.$response->body();
                    }
                } catch (\Exception $e) {
                    $token_data['error'] = $e->getMessage();
                }

                break;
        }

        return $token_data;
    }

    /**
     * Disconnect.
     */
    public static function disconnect(string $provider_code, string $redirect_uri): \Illuminate\Http\RedirectResponse
    {
        switch ($provider_code) {
            case self::PROVIDER_MICROSOFT:
                return redirect()->away('https://login.microsoftonline.com/common/oauth2/v2.0/logout?post_logout_redirect_uri='.urlencode($redirect_uri));
        }

        return redirect()->to($redirect_uri);
    }
}
