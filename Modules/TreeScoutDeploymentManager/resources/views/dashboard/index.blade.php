@extends('tsdm::layouts.master')

@section('module-content')

{{-- Page header --}}
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Control Tower</h1>
        <p class="mt-1 text-sm text-gray-500">System-wide deployment health & active operations.</p>
    </div>
    @can('manage_tsdm')
    <a href="{{ route('tsdm.deployments.create') }}"
       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-primary-700">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Deployment
    </a>
    @endcan
</div>

{{-- ── KPI TILES ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @php
        $tiles = [
            ['label' => 'Total Deployments',  'value' => $stats['total'],     'color' => 'border-primary-500'],
            ['label' => 'Active',             'value' => $stats['active'],    'color' => 'border-success-500'],
            ['label' => 'Pending Activation', 'value' => $stats['pending'],   'color' => 'border-warning-500'],
            ['label' => 'Suspended/Revoked',  'value' => $stats['suspended'], 'color' => 'border-danger-500'],
        ];
    @endphp

    @foreach ($tiles as $tile)
    <div class="bg-white rounded-lg shadow-sm border-l-4 {{ $tile['color'] }} px-5 py-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $tile['label'] }}</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $tile['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── ALERTS ─────────────────────────────────────────────────────────── --}}
@if ($expiringActivations->isNotEmpty())
<div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
    <div class="flex items-start">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">
                {{ $expiringActivations->count() }} activation {{ Str::plural('code', $expiringActivations->count()) }} expiring within 2 hours
            </p>
            <ul class="mt-1 text-xs text-amber-700 space-y-0.5">
                @foreach ($expiringActivations as $exp)
                <li>
                    <span class="font-mono">{{ $exp->activation_code }}</span>
                    &mdash; {{ $exp->deploymentRecord?->name ?? 'Unknown' }}
                    &mdash; expires {{ $exp->expires_at->diffForHumans() }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

{{-- ── OPERATIONS TABLE ────────────────────────────────────────────────── --}}
<div class="bg-white shadow-sm rounded-lg overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800">Active Operations</h2>
        <a href="{{ route('tsdm.deployments.index') }}" class="text-xs text-primary-600 hover:underline">View all</a>
    </div>

    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deployment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Environment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modules</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @forelse ($recentDeployments as $dep)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">{{ $dep->name }}</div>
                    <div class="text-xs text-gray-400">ID {{ $dep->id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ $dep->environment === 'production' ? 'bg-red-100 text-red-800' : ($dep->environment === 'staging' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ ucfirst($dep->environment) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $colors = ['active' => 'bg-green-100 text-green-800', 'pending' => 'bg-yellow-100 text-yellow-800', 'suspended' => 'bg-red-100 text-red-800', 'revoked' => 'bg-red-100 text-red-800'];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors[$dep->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($dep->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $dep->last_seen_at ? $dep->last_seen_at->diffForHumans() : 'Never' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $dep->deployedModules?->count() ?? 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <a href="{{ route('tsdm.deployments.show', $dep) }}"
                       class="text-primary-600 hover:text-primary-800 font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">
                    No deployments yet. <a href="{{ route('tsdm.deployments.create') }}" class="text-primary-600 hover:underline">Create the first one.</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── STALE DEPLOYMENTS CARD ──────────────────────────────────────────── --}}
@if ($staleDeployments->isNotEmpty())
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 border-l-4 border-l-danger-500">
        <h2 class="text-base font-semibold text-red-700">Stale Deployments — Not seen in 30+ days</h2>
        <p class="text-xs text-gray-500 mt-0.5">These are marked active but have not checked in recently. Consider investigating or revoking.</p>
    </div>
    <ul class="divide-y divide-gray-100">
        @foreach ($staleDeployments as $dep)
        <li class="px-6 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-medium text-gray-900">{{ $dep->name }}</span>
                <span class="ml-2 text-xs text-gray-400">Last seen: {{ $dep->last_seen_at ? $dep->last_seen_at->diffForHumans() : 'Never' }}</span>
            </div>
            <a href="{{ route('tsdm.deployments.show', $dep) }}" class="text-xs text-primary-600 hover:underline">Inspect</a>
        </li>
        @endforeach
    </ul>
</div>
@endif

@endsection
