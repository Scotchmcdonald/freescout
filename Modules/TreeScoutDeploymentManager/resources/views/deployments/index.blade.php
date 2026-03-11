@extends('tsdm::layouts.master')

@section('module-content')

<div class="flex justify-between items-center mb-6">
    <div>
        <nav class="text-xs text-gray-400 mb-1">
            <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
            <span class="mx-1">/</span>
            <span class="text-gray-600">Deployments</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">Deployments</h1>
    </div>
    @can('manage_tsdm')
    <a href="{{ route('tsdm.deployments.create') }}"
       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Deployment
    </a>
    @endcan
</div>

<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Env</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Git Provider</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modules</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @forelse ($deployments as $dep)
            @php
                $statusColors = ['active' => 'bg-green-100 text-green-800', 'pending' => 'bg-yellow-100 text-yellow-800', 'suspended' => 'bg-red-100 text-red-800', 'revoked' => 'bg-red-100 text-red-800'];
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">{{ $dep->name }}</div>
                    @if($dep->server_ip)
                        <div class="text-xs font-mono text-gray-400">{{ $dep->server_ip }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                        {{ $dep->environment === 'production' ? 'bg-red-100 text-red-800' : ($dep->environment === 'staging' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ ucfirst($dep->environment) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($dep->git_provider) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$dep->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($dep->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $dep->last_seen_at ? $dep->last_seen_at->diffForHumans() : '—' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $dep->deployed_modules_count ?? $dep->deployedModules->count() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <a href="{{ route('tsdm.deployments.show', $dep) }}"
                       class="text-primary-600 hover:text-primary-900 font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                    No deployments found.
                    @can('manage_tsdm')
                    <a href="{{ route('tsdm.deployments.create') }}" class="text-primary-600 hover:underline ml-1">Create one now.</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($deployments->hasPages())
    <div class="border-t border-gray-200 px-6 py-3">
        {{ $deployments->links() }}
    </div>
    @endif
</div>

@endsection
