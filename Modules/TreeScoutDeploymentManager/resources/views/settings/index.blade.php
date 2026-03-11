@extends('tsdm::layouts.master')

@section('module-content')

<div class="mb-6">
    <nav class="text-xs text-gray-400 mb-1">
        <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
        <span class="mx-1">/</span>
        <span class="text-gray-600">Settings</span>
    </nav>
    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
    <p class="text-sm text-gray-500 mt-1">Read-only view of the current configuration. Changes require environment variable updates.</p>
</div>

{{-- ── INFO CARD ────────────────────────────────────────────────────────── --}}
<div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
    <svg class="w-5 h-5 text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div class="text-sm text-blue-800">
        <p class="font-semibold">Settings are environment-variable driven.</p>
        <p class="mt-0.5">Edit your <span class="font-mono">.env</span> file on the server and clear the config cache (<span class="font-mono">php artisan config:clear</span>) to apply changes.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Git Provider --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Git Provider</h3>
        </div>
        @php
            $providerRows = [
                ['var' => 'TSDM_GIT_PROVIDER',      'value' => $settings['git_provider']],
                ['var' => 'TSDM_GITLAB_HOST',        'value' => $settings['gitlab_host']],
                ['var' => 'TSDM_GITLAB_PROJECT_ID',  'value' => $settings['gitlab_project_id'] ?? '(not set)'],
            ];
        @endphp
        @foreach ($providerRows as $row)
        <div class="px-5 py-3 flex justify-between items-center border-b border-gray-50 last:border-0">
            <span class="font-mono text-xs text-gray-500">{{ $row['var'] }}</span>
            <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- Activation --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Activation</h3>
        </div>
        @php
            $activationRows = [
                ['var' => 'TSDM_OTAC_TTL_HOURS', 'value' => $settings['activation_ttl_hours'] . ' hours'],
                ['var' => 'TSDM_IP_PINNING',     'value' => $settings['enable_ip_pinning'] ? 'Enabled' : 'Disabled'],
            ];
        @endphp
        @foreach ($activationRows as $row)
        <div class="px-5 py-3 flex justify-between items-center border-b border-gray-50 last:border-0">
            <span class="font-mono text-xs text-gray-500">{{ $row['var'] }}</span>
            <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- Secret Status --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden md:col-span-2">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Secret / Credential Status</h3>
            <p class="text-xs text-gray-400 mt-0.5">Values are never displayed — only whether the variable is configured.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach ($secretStatus as $var => $configured)
            <div class="px-5 py-3 flex justify-between items-center">
                <span class="font-mono text-xs text-gray-500">{{ $var }}</span>
                @if ($configured)
                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Configured
                </span>
                @else
                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Not set
                </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Troubleshooting Card --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden md:col-span-2 border-l-4 border-warning-500">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-amber-700">Troubleshooting: Activation Failures</h3>
        </div>
        <div class="px-5 py-4 grid md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-bold text-gray-600 uppercase mb-1">What happened?</p>
                <p class="text-sm text-gray-500">A client server called <span class="font-mono">/api/tsdm/activate</span> but received an error or no token.</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-600 uppercase mb-1">Why?</p>
                <ul class="text-sm text-gray-500 list-disc pl-4 space-y-1">
                    <li>Git provider credentials not configured (see secrets above)</li>
                    <li>OTAC expired or already used</li>
                    <li>IP pinning mismatch (if enabled)</li>
                    <li>GitLab project ID wrong or missing</li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-600 uppercase mb-1">What now?</p>
                <ul class="text-sm text-gray-500 list-disc pl-4 space-y-1">
                    <li>Check the Audit Log on the deployment's detail page</li>
                    <li>Verify <span class="font-mono">TSDM_GITLAB_ADMIN_TOKEN</span> has <span class="font-mono">api</span> scope</li>
                    <li>Issue a new activation code with a fresh TTL</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@endsection
