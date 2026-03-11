<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activation Code Settings
    |--------------------------------------------------------------------------
    */
    'activation' => [
        // Default TTL in hours for new OTACs
        'ttl_hours' => env('TSDM_OTAC_TTL_HOURS', 24),

        // Minimum access level granted to issued tokens (GitLab: 20 = Reporter)
        'gitlab_access_level' => env('TSDM_GITLAB_ACCESS_LEVEL', 20),

        // Scopes granted to all issued tokens (GitLab project access token scopes)
        'default_scopes' => ['read_repository'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Git Provider
    |--------------------------------------------------------------------------
    | provider: 'gitlab' or 'github'
    |
    | For GitLab, supply:
    |   - admin_token: a Personal Access Token with `api` scope on the server
    |   - default_project_id: the project ID of the private module repository
    |
    | For GitHub, set provider to 'github' and supply:
    |   - app_id, private_key_path, installation_id
    |   These are used to generate short-lived Installation Access Tokens.
    */
    'git' => [
        'provider' => env('TSDM_GIT_PROVIDER', 'gitlab'),

        'gitlab' => [
            'host'               => env('TSDM_GITLAB_HOST', 'https://gitlab.com'),
            'admin_token'        => env('TSDM_GITLAB_ADMIN_TOKEN'),
            'default_project_id' => env('TSDM_GITLAB_PROJECT_ID'),
        ],

        'github' => [
            'app_id'           => env('TSDM_GITHUB_APP_ID'),
            'private_key_path' => env('TSDM_GITHUB_PRIVATE_KEY_PATH'),
            'installation_id'  => env('TSDM_GITHUB_INSTALLATION_ID'),

            // --- TEST / SIMPLE MODE ---
            // If set, this PAT is returned directly instead of issuing an App token.
            // Use a classic PAT with `repo` (read) scope for local testing.
            // DO NOT use in production — rotate after testing.
            'static_pat'       => env('TSDM_GITHUB_STATIC_PAT'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Store the redeeming IP and lock subsequent calls to same IP (optional)
        'enable_ip_pinning' => env('TSDM_IP_PINNING', false),

        // Encrypt the issued token at rest using Laravel encryption
        'encrypt_issued_tokens' => true,
    ],

];
