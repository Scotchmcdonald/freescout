@extends('layouts.app')

@section('title', __('Module Activity Log'))

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">{{ __('Module Activity Log') }}</h1>
                <p class="mt-1 text-sm text-neutral-600">{{ __('Track all module operations and changes') }}</p>
            </div>
            <a href="{{ route('modules') }}" class="px-4 py-2 bg-neutral-600 text-white text-sm font-medium rounded-md hover:bg-neutral-700 transition-colors">
                {{ __('Back to Modules') }}
            </a>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('Date & Time') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('User') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('Module') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('Action') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('Details') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    {{ __('IP Address') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        {{ $log->created_at->format('M d, Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                        {{ $log->user ? $log->user->email : __('System') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                                        {{ $log->module_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $actionColors = [
                                                'install' => 'bg-success-100 text-success-800',
                                                'update' => 'bg-primary-100 text-primary-800',
                                                'enable' => 'bg-success-100 text-success-800',
                                                'disable' => 'bg-warning-100 text-warning-800',
                                                'delete' => 'bg-danger-100 text-danger-800',
                                            ];
                                            $colorClass = $actionColors[$log->action] ?? 'bg-neutral-100 text-neutral-800';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-neutral-500 max-w-md">
                                        @if($log->metadata)
                                            <div class="space-y-1">
                                                @if(isset($log->metadata['repo_url']))
                                                    <div class="text-xs">
                                                        <strong class="text-neutral-700">{{ __('Repo:') }}</strong> 
                                                        <a href="{{ $log->metadata['repo_url'] }}" 
                                                           target="_blank" 
                                                           class="font-mono text-xs text-primary-600 hover:text-primary-800 hover:underline break-all"
                                                           title="{{ $log->metadata['repo_url'] }}">
                                                            {{ $log->metadata['repo_url'] }}
                                                        </a>
                                                    </div>
                                                @endif
                                                @if(isset($log->metadata['commit']))
                                                    <div class="text-xs">
                                                        <strong class="text-neutral-700">{{ __('Commit:') }}</strong> 
                                                        <span class="font-mono text-xs text-neutral-600">{{ $log->metadata['commit'] }}</span>
                                                    </div>
                                                @endif
                                                @if(isset($log->metadata['branch']))
                                                    <div class="text-xs">
                                                        <strong class="text-neutral-700">{{ __('Branch:') }}</strong> 
                                                        <span class="font-mono text-xs text-neutral-600">{{ $log->metadata['branch'] }}</span>
                                                    </div>
                                                @endif
                                                @if(isset($log->metadata['method']))
                                                    <div class="text-xs">
                                                        <strong class="text-neutral-700">{{ __('Method:') }}</strong> 
                                                        <span class="text-xs text-neutral-600">{{ ucfirst($log->metadata['method']) }}</span>
                                                    </div>
                                                @endif
                                                @if(isset($log->metadata['error']))
                                                    <div class="text-xs text-danger-600 mt-1">
                                                        <strong>{{ __('Error:') }}</strong> 
                                                        <span class="break-words">{{ $log->metadata['error'] }}</span>
                                                    </div>
                                                @endif
                                                @if(isset($log->metadata['failed']) && $log->metadata['failed'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 mt-1">
                                                        {{ __('Failed') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-neutral-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 font-mono">
                                        {{ $log->ip_address ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-neutral-900">{{ __('No Activity Logs') }}</h3>
                    <p class="mt-1 text-sm text-neutral-500">{{ __('Module operations will be logged here') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
