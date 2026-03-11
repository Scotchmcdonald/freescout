<?php

namespace Modules\TreeScoutDeploymentManager\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $settings = [
            'git_provider'          => config('tsdm.git.provider', 'gitlab'),
            'gitlab_host'           => config('tsdm.git.gitlab.host', 'https://gitlab.com'),
            'gitlab_project_id'     => config('tsdm.git.gitlab.default_project_id'),
            'activation_ttl_hours'  => config('tsdm.activation.ttl_hours', 24),
            'enable_ip_pinning'     => config('tsdm.security.enable_ip_pinning', false),
        ];

        // Mask secrets — only show whether they are configured, never the value
        $secretStatus = [
            'TSDM_GITLAB_ADMIN_TOKEN'    => filled(config('tsdm.git.gitlab.admin_token')),
            'TSDM_GITHUB_APP_ID'         => filled(config('tsdm.git.github.app_id')),
            'TSDM_GITHUB_PRIVATE_KEY'    => filled(config('tsdm.git.github.private_key_path')),
            'TSDM_GITHUB_INSTALLATION'   => filled(config('tsdm.git.github.installation_id')),
        ];

        return view('tsdm::settings.index', compact('settings', 'secretStatus'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Settings are environment-variable driven.
        // Direct editing is out of scope for the UI — guide users to .env.
        return back()->with('info', 'Settings are managed via environment variables. Update your .env file and re-deploy.');
    }
}
