@extends('tsdm::layouts.master')

@section('module-content')

{{-- Breadcrumb --}}
<nav class="text-xs text-gray-400 mb-1">
    <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
    <span class="mx-1">/</span>
    <a href="{{ route('tsdm.deployments.index') }}" class="hover:text-primary-600">Deployments</a>
    <span class="mx-1">/</span>
    <span class="text-gray-600">{{ $deployment->name }}</span>
</nav>

{{-- Page header --}}
<div class="flex justify-between items-start mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $deployment->name }}</h1>
        <div class="mt-1 flex items-center gap-3">
            @php $sc = ['active' => 'bg-green-100 text-green-800', 'pending' => 'bg-yellow-100 text-yellow-800', 'suspended' => 'bg-red-100 text-red-800', 'revoked' => 'bg-red-100 text-red-800']; @endphp
            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc[$deployment->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ ucfirst($deployment->status) }}
            </span>
            <span class="text-xs text-gray-400 font-mono">{{ $deployment->server_ip ?? 'No IP recorded' }}</span>
        </div>
    </div>
    <div class="flex gap-2">
        @can('manage_tsdm')
        <a href="{{ route('tsdm.deployments.edit', $deployment) }}"
           class="px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Edit</a>

        @if ($deployment->status !== 'revoked')
        <form method="POST" action="{{ route('tsdm.deployments.revoke', $deployment) }}"
              onsubmit="return confirm('Revoke this deployment? All pending activation codes will be expired immediately.')">
            @csrf
            <button type="submit"
                    class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                Revoke
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('tsdm.deployments.reinstate', $deployment) }}">
            @csrf
            <button type="submit"
                    class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                Reinstate
            </button>
        </form>
        @endif
        @endcan
    </div>
</div>

{{-- Alpine.js tab state --}}
<div x-data="{ tab: 'overview' }">

    {{-- Tab bar --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-6">
            @foreach ([['id' => 'overview', 'label' => 'Overview'], ['id' => 'modules', 'label' => 'Installed Modules'], ['id' => 'activations', 'label' => 'Activations'], ['id' => 'audit', 'label' => 'Audit Log']] as $t)
            <button @click="tab = '{{ $t['id'] }}'"
                    :class="tab === '{{ $t['id'] }}' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="border-b-2 py-3 px-1 text-sm font-medium transition-colors">
                {{ $t['label'] }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ── OVERVIEW TAB ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'overview'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Detail card --}}
            <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
                <div class="px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-700">Deployment Details</h3>
                </div>
                @php
                    $details = [
                        'Environment'   => ucfirst($deployment->environment),
                        'Git Provider'  => ucfirst($deployment->git_provider),
                        'Project ID'    => $deployment->git_project_id ?? '—',
                        'Server IP'     => $deployment->server_ip ?? '—',
                        'App Version'   => $deployment->app_version ?? '—',
                        'Last Seen'     => $deployment->last_seen_at ? $deployment->last_seen_at->format('Y-m-d H:i') . ' (' . $deployment->last_seen_at->diffForHumans() . ')' : 'Never',
                        'Created'       => $deployment->created_at->format('Y-m-d'),
                    ];
                @endphp
                @foreach ($details as $key => $val)
                <div class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-500">{{ $key }}</span>
                    <span class="text-gray-900 font-medium">{{ $val }}</span>
                </div>
                @endforeach
            </div>

            {{-- Notes + Issue new code --}}
            <div class="space-y-4">
                @if ($deployment->notes)
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $deployment->notes }}</p>
                </div>
                @endif

                @can('issue_tsdm_activations')
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Issue Activation Code</h3>
                    <form method="POST" action="{{ route('tsdm.activations.store') }}">
                        @csrf
                        <input type="hidden" name="deployment_record_id" value="{{ $deployment->id }}">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Label (optional)</label>
                                <input type="text" name="label" placeholder="e.g. Initial install"
                                       class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Expires in (hours)</label>
                                <input type="number" name="ttl_hours" value="{{ config('tsdm.activation.ttl_hours', 24) }}"
                                       min="1" max="168"
                                       class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
                                Generate Code
                            </button>
                        </div>
                    </form>
                </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── MODULES TAB ───────────────────────────────────────────────── --}}
    <div x-show="tab === 'modules'" x-cloak>
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Installed Modules</h3>
            </div>
            @if ($deployment->deployedModules->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                No modules tracked yet. They will appear after first activation.
            </div>
            @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Installed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($deployment->deployedModules as $mod)
                    @php $mc = ['active' => 'bg-green-100 text-green-800', 'disabled' => 'bg-yellow-100 text-yellow-800', 'error' => 'bg-red-100 text-red-800']; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $mod->module_name }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500 font-mono">{{ $mod->module_version ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $mc[$mod->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($mod->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $mod->installed_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $mod->last_updated_at?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- ── ACTIVATIONS TAB ───────────────────────────────────────────── --}}
    <div x-show="tab === 'activations'" x-cloak>
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Activation History</h3>
            </div>
            @if ($deployment->activations->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No activation codes issued yet.</div>
            @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Used At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From IP</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($deployment->activations as $act)
                    @php
                        $sc2 = ['Used' => 'bg-gray-100 text-gray-600', 'Valid' => 'bg-green-100 text-green-700', 'Expired' => 'bg-red-100 text-red-700'];
                        $stateLabel = $act->statusLabel();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-mono text-sm text-gray-900">{{ $act->activation_code }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $act->label ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc2[$stateLabel] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $stateLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $act->expires_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $act->used_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm font-mono text-gray-400">{{ $act->redeemed_from_ip ?? '—' }}</td>
                        <td class="px-6 py-3 text-right">
                            @if ($act->isValid())
                            @can('issue_tsdm_activations')
                            <form method="POST" action="{{ route('tsdm.activations.expire', $act) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Expire</button>
                            </form>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- ── AUDIT LOG TAB ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'audit'" x-cloak>
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Activation Audit Log</h3>
                <p class="text-xs text-gray-400 mt-0.5">Immutable log of all activation attempts (last 50).</p>
            </div>
            @if ($auditLog->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No audit entries yet.</div>
            @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outcome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($auditLog as $log)
                    @php
                        $oc = ['success' => 'bg-green-100 text-green-700', 'invalid_code' => 'bg-red-100 text-red-700', 'expired' => 'bg-orange-100 text-orange-700', 'already_used' => 'bg-yellow-100 text-yellow-700', 'provider_error' => 'bg-red-100 text-red-700'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-xs text-gray-500 font-mono whitespace-nowrap">{{ $log->attempted_at }}</td>
                        <td class="px-6 py-3 text-xs font-mono text-gray-700">{{ $log->activation_code }}</td>
                        <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $log->attempt_ip ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $oc[$log->outcome] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ str_replace('_', ' ', $log->outcome) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-400 max-w-xs truncate">{{ $log->error_detail ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

</div>{{-- /x-data --}}

@endsection
