@extends('layouts.app')

@section('title', 'Webhook Gateway')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold" style="color: var(--theme-gray-900, #111827);">
                    Webhook Gateway
                </h1>
                <p class="mt-1 text-sm" style="color: var(--theme-gray-600, #4B5563);">
                    Monitor and manage Google Workspace push notification channels
                </p>
            </div>
            <button 
                onclick="document.getElementById('createChannelModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: var(--theme-primary-600, #4F46E5); color: white;"
                onmouseover="this.style.backgroundColor='var(--theme-primary-700, #4338CA)'"
                onmouseout="this.style.backgroundColor='var(--theme-primary-600, #4F46E5)'"
                aria-label="Create new webhook channel"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Channel
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6" x-data="{ activeFilter: 'all' }">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Total Channels -->
            <div @click="activeFilter = 'all'" 
                 class="rounded-lg p-5 shadow-sm cursor-pointer transition-colors border-l-4" 
                 style="background-color: var(--theme-background, white); border-top: 1px solid var(--theme-gray-200, #E5E7EB); border-right: 1px solid var(--theme-gray-200, #E5E7EB); border-bottom: 1px solid var(--theme-gray-200, #E5E7EB); border-left-color: var(--theme-primary-500, #6366F1);"
                 :style="activeFilter === 'all' ? 'background-color: var(--theme-primary-50, #EEF2FF);' : ''"
                 onmouseover="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-primary-50, #EEF2FF)'"
                 onmouseout="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-background, white)'"
                 :data-active="activeFilter === 'all' ? 'true' : 'false'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium" style="color: var(--theme-gray-600, #4B5563);">Total Channels</p>
                        <p class="mt-2 text-3xl font-semibold" style="color: var(--theme-gray-900, #111827);">
                            {{ $metrics['total'] }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full" style="background-color: var(--theme-primary-100, #E0E7FF);">
                        <svg class="w-6 h-6" style="color: var(--theme-primary-600, #4F46E5);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Channels -->
            <div @click="activeFilter = 'active'" 
                 class="rounded-lg p-5 shadow-sm cursor-pointer transition-colors border-l-4" 
                 style="background-color: var(--theme-background, white); border-top: 1px solid var(--theme-gray-200, #E5E7EB); border-right: 1px solid var(--theme-gray-200, #E5E7EB); border-bottom: 1px solid var(--theme-gray-200, #E5E7EB); border-left-color: var(--theme-success-500, #10B981);"
                 :style="activeFilter === 'active' ? 'background-color: var(--theme-success-50, #ECFDF5);' : ''"
                 onmouseover="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-success-50, #ECFDF5)'"
                 onmouseout="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-background, white)'"
                 :data-active="activeFilter === 'active' ? 'true' : 'false'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium" style="color: var(--theme-gray-600, #4B5563);">Active</p>
                        <p class="mt-2 text-3xl font-semibold" style="color: var(--theme-success-600, #059669);">
                            {{ $metrics['active'] }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full" style="background-color: var(--theme-success-100, #D1FAE5);">
                        <svg class="w-6 h-6" style="color: var(--theme-success-600, #059669);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expired Channels -->
            <div @click="activeFilter = 'expired'" 
                 class="rounded-lg p-5 shadow-sm cursor-pointer transition-colors border-l-4" 
                 style="background-color: var(--theme-background, white); border-top: 1px solid var(--theme-gray-200, #E5E7EB); border-right: 1px solid var(--theme-gray-200, #E5E7EB); border-bottom: 1px solid var(--theme-gray-200, #E5E7EB); border-left-color: var(--theme-danger-500, #EF4444);"
                 :style="activeFilter === 'expired' ? 'background-color: var(--theme-danger-50, #FEF2F2);' : ''"
                 onmouseover="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-danger-50, #FEF2F2)'"
                 onmouseout="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-background, white)'"
                 :data-active="activeFilter === 'expired' ? 'true' : 'false'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium" style="color: var(--theme-gray-600, #4B5563);">Expired</p>
                        <p class="mt-2 text-3xl font-semibold" style="color: var(--theme-danger-600, #DC2626);">
                            {{ $metrics['expired'] }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full" style="background-color: var(--theme-danger-100, #FEE2E2);">
                        <svg class="w-6 h-6" style="color: var(--theme-danger-600, #DC2626);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div @click="activeFilter = 'expiring'" 
                 class="rounded-lg p-5 shadow-sm cursor-pointer transition-colors border-l-4" 
                 style="background-color: var(--theme-background, white); border-top: 1px solid var(--theme-gray-200, #E5E7EB); border-right: 1px solid var(--theme-gray-200, #E5E7EB); border-bottom: 1px solid var(--theme-gray-200, #E5E7EB); border-left-color: var(--theme-warning-500, #F59E0B);"
                 :style="activeFilter === 'expiring' ? 'background-color: var(--theme-warning-50, #FFFBEB);' : ''"
                 onmouseover="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-warning-50, #FFFBEB)'"
                 onmouseout="if (this.getAttribute('data-active') !== 'true') this.style.backgroundColor='var(--theme-background, white)'"
                 :data-active="activeFilter === 'expiring' ? 'true' : 'false'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium" style="color: var(--theme-gray-600, #4B5563);">Expiring Soon</p>
                        <p class="mt-2 text-3xl font-semibold" style="color: var(--theme-warning-600, #D97706);">
                            {{ $metrics['expiring_soon'] }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full" style="background-color: var(--theme-warning-100, #FEF3C7);">
                        <svg class="w-6 h-6" style="color: var(--theme-warning-600, #D97706);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Notifications -->
            <div @click="activeFilter = 'all'" 
                 class="rounded-lg p-5 shadow-sm cursor-pointer transition-colors border-l-4" 
                 style="background-color: var(--theme-background, white); border-top: 1px solid var(--theme-gray-200, #E5E7EB); border-right: 1px solid var(--theme-gray-200, #E5E7EB); border-bottom: 1px solid var(--theme-gray-200, #E5E7EB); border-left-color: var(--theme-gray-400, #9CA3AF);"
                 onmouseover="this.style.backgroundColor='var(--theme-gray-50, #F9FAFB)'"
                 onmouseout="this.style.backgroundColor='var(--theme-background, white)'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium" style="color: var(--theme-gray-600, #4B5563);">Notifications</p>
                        <p class="mt-2 text-3xl font-semibold" style="color: var(--theme-gray-900, #111827);">
                            {{ number_format($metrics['total_notifications']) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full" style="background-color: var(--theme-gray-100, #F3F4F6);">
                        <svg class="w-6 h-6" style="color: var(--theme-gray-600, #4B5563);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

    <!-- Channels Table -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg shadow-sm overflow-hidden" style="background-color: var(--theme-background, white); border: 1px solid var(--theme-gray-200, #E5E7EB);">
            @if($channels->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-12" role="status" aria-live="polite">
                    <svg class="mx-auto h-12 w-12" style="color: var(--theme-gray-400, #9CA3AF);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium" style="color: var(--theme-gray-900, #111827);">No webhook channels configured</h3>
                    <p class="mt-2 text-sm" style="color: var(--theme-gray-600, #4B5563);">
                        Get started by creating your first push notification channel
                    </p>
                    <button 
                        onclick="document.getElementById('createChannelModal').classList.remove('hidden')"
                        class="mt-4 inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="background-color: var(--theme-primary-600, #4F46E5); color: white;"
                        onmouseover="this.style.backgroundColor='var(--theme-primary-700, #4338CA)'"
                        onmouseout="this.style.backgroundColor='var(--theme-primary-600, #4F46E5)'"
                        aria-label="Create your first webhook channel"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Channel
                    </button>
                </div>
            @else
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y" style="border-color: var(--theme-gray-200, #E5E7EB);" role="table">
                        <thead style="background-color: var(--theme-gray-50, #F9FAFB);">
                            <tr role="row">
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Resource
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Channel ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Last Notification
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Expiration
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Notifications
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: var(--theme-gray-700, #374151);">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="background-color: white; border-color: var(--theme-gray-200, #E5E7EB);">
                            @foreach($channels as $channel)
                                @php
                                    $health = $channel->getHealthStatus();
                                    $showChannel = true;
                                    
                                    // Filter logic based on active filter
                                    if (request()->get('filter') === 'active' && $health['status'] !== 'healthy') {
                                        $showChannel = false;
                                    } elseif (request()->get('filter') === 'expired' && $health['status'] !== 'expired') {
                                        $showChannel = false;
                                    } elseif (request()->get('filter') === 'expiring' && $health['status'] !== 'expiring') {
                                        $showChannel = false;
                                    }
                                @endphp
                                <tr role="row" 
                                    x-show="activeFilter === 'all' || 
                                           (activeFilter === 'active' && {{ $channel->is_active && !$channel->isExpired() && !$channel->isExpiringSoon() ? 'true' : 'false' }}) || 
                                           (activeFilter === 'expired' && {{ $channel->isExpired() ? 'true' : 'false' }}) || 
                                           (activeFilter === 'expiring' && {{ $channel->isExpiringSoon() ? 'true' : 'false' }})"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg" style="background-color: var(--theme-primary-100, #E0E7FF);">
                                                <svg class="h-6 w-6" style="color: var(--theme-primary-600, #4F46E5);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium" style="color: var(--theme-gray-900, #111827);">
                                                    {{ ucfirst($channel->resource_type) }}
                                                </div>
                                                <div class="text-sm" style="color: var(--theme-gray-500, #6B7280);" title="{{ $channel->resource_id }}">
                                                    {{ Str::limit($channel->resource_id, 30) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono" style="color: var(--theme-gray-900, #111827);">
                                            {{ Str::limit($channel->channel_id, 20) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            style="background-color: var(--theme-{{ $health['color'] }}-100, #E0E7FF); color: var(--theme-{{ $health['color'] }}-800, #1E40AF);">
                                            {{ ucfirst($health['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-gray-600, #4B5563);">
                                        @if($channel->last_notification_at)
                                            {{ $channel->last_notification_at->diffForHumans() }}
                                        @else
                                            <span style="color: var(--theme-gray-400, #9CA3AF);">Never</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-gray-600, #4B5563);">
                                        @if($channel->isExpired())
                                            <span style="color: var(--theme-danger-600, #DC2626);">
                                                Expired {{ $channel->expiration_time->diffForHumans() }}
                                            </span>
                                        @else
                                            <span>{{ $channel->expires_in }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-gray-900, #111827);">
                                        {{ number_format($channel->notification_count) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button 
                                                onclick="testChannel({{ $channel->id }}, '{{ $channel->channel_id }}')"
                                                class="p-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                                style="color: var(--theme-primary-600, #4F46E5);"
                                                onmouseover="this.style.backgroundColor='var(--theme-primary-50, #EEF2FF)'"
                                                onmouseout="this.style.backgroundColor='transparent'"
                                                aria-label="Test webhook for channel {{ Str::limit($channel->channel_id, 20) }}"
                                                title="Test Webhook"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                            <button 
                                                onclick="openRenewModal({{ $channel->id }}, '{{ $channel->channel_id }}')"
                                                class="p-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                                style="color: var(--theme-success-600, #059669);"
                                                onmouseover="this.style.backgroundColor='var(--theme-success-50, #ECFDF5)'"
                                                onmouseout="this.style.backgroundColor='transparent'"
                                                aria-label="Renew channel {{ Str::limit($channel->channel_id, 20) }}"
                                                title="Renew Channel"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                            <button 
                                                onclick="confirmStop({{ $channel->id }}, '{{ $channel->channel_id }}')"
                                                class="p-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                                style="color: var(--theme-danger-600, #DC2626);"
                                                onmouseover="this.style.backgroundColor='var(--theme-danger-50, #FEF2F2)'"
                                                onmouseout="this.style.backgroundColor='transparent'"
                                                aria-label="Stop channel {{ Str::limit($channel->channel_id, 20) }}"
                                                title="Stop Channel"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Channel Modal -->
<div id="createChannelModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="createChannelTitle">
    <div class="rounded-lg shadow-xl max-w-2xl w-full mx-4" style="background-color: var(--theme-background, white);">
        <form action="{{ route('webhooks.gateway.store') }}" method="POST" id="createChannelForm">
            @csrf
            <div class="px-6 py-4 border-b" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <h3 id="createChannelTitle" class="text-lg font-semibold" style="color: var(--theme-gray-900, #111827);">
                    Create Webhook Channel
                </h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="resource_type" class="block text-sm font-medium mb-1" style="color: var(--theme-gray-700, #374151);">
                        Resource Type <span style="color: var(--theme-danger-600, #DC2626);" aria-hidden="true">*</span>
                    </label>
                    <select 
                        id="resource_type" 
                        name="resource_type" 
                        required
                        aria-required="true"
                        aria-describedby="resource_type_help"
                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                        style="border-color: var(--theme-gray-300, #D1D5DB); color: var(--theme-gray-900, #111827);"
                        onfocus="this.style.borderColor='var(--theme-primary-500, #6366F1)'; this.style.boxShadow='0 0 0 3px var(--theme-primary-100, #E0E7FF)'"
                        onblur="this.style.borderColor='var(--theme-gray-300, #D1D5DB)'; this.style.boxShadow='none'"
                    >
                        <option value="">Select resource type</option>
                        <option value="directory">Directory (Users/Groups)</option>
                        <option value="drive">Google Drive</option>
                        <option value="calendar">Calendar</option>
                        <option value="gmail">Gmail</option>
                    </select>
                    <p id="resource_type_help" class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        The Google Workspace resource to monitor for changes
                    </p>
                </div>

                <div>
                    <label for="resource_id" class="block text-sm font-medium mb-1" style="color: var(--theme-gray-700, #374151);">
                        Resource ID <span style="color: var(--theme-danger-600, #DC2626);" aria-hidden="true">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="resource_id" 
                        name="resource_id" 
                        required
                        aria-required="true"
                        aria-describedby="resource_id_help"
                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                        style="border-color: var(--theme-gray-300, #D1D5DB); color: var(--theme-gray-900, #111827);"
                        onfocus="this.style.borderColor='var(--theme-primary-500, #6366F1)'; this.style.boxShadow='0 0 0 3px var(--theme-primary-100, #E0E7FF)'"
                        onblur="this.style.borderColor='var(--theme-gray-300, #D1D5DB)'; this.style.boxShadow='none'"
                        placeholder="my_primary_domain"
                    />
                    <p id="resource_id_help" class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        The identifier for the specific resource (e.g., domain name for directory)
                    </p>
                </div>

                <div>
                    <label for="webhook_url" class="block text-sm font-medium mb-1" style="color: var(--theme-gray-700, #374151);">
                        Webhook URL <span style="color: var(--theme-danger-600, #DC2626);" aria-hidden="true">*</span>
                    </label>
                    <input 
                        type="url" 
                        id="webhook_url" 
                        name="webhook_url" 
                        required
                        aria-required="true"
                        aria-describedby="webhook_url_help"
                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                        style="border-color: var(--theme-gray-300, #D1D5DB); color: var(--theme-gray-900, #111827);"
                        onfocus="this.style.borderColor='var(--theme-primary-500, #6366F1)'; this.style.boxShadow='0 0 0 3px var(--theme-primary-100, #E0E7FF)'"
                        onblur="this.style.borderColor='var(--theme-gray-300, #D1D5DB)'; this.style.boxShadow='none'"
                        placeholder="https://your-domain.com/api/webhooks/google"
                    />
                    <p id="webhook_url_help" class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        The URL where Google will send push notifications
                    </p>
                </div>

                <div>
                    <label for="duration_hours" class="block text-sm font-medium mb-1" style="color: var(--theme-gray-700, #374151);">
                        Duration (hours) <span style="color: var(--theme-danger-600, #DC2626);" aria-hidden="true">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="duration_hours" 
                        name="duration_hours" 
                        min="1" 
                        max="43200" 
                        value="24"
                        step="1"
                        required
                        aria-required="true"
                        aria-describedby="duration_hours_help"
                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                        style="border-color: var(--theme-gray-300, #D1D5DB); color: var(--theme-gray-900, #111827);"
                        onfocus="this.style.borderColor='var(--theme-primary-500, #6366F1)'; this.style.boxShadow='0 0 0 3px var(--theme-primary-100, #E0E7FF)'"
                        onblur="this.style.borderColor='var(--theme-gray-300, #D1D5DB)'; this.style.boxShadow='none'"
                    />
                    <p id="duration_hours_help" class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        Channel lifetime (maximum 43,200 hours / 30 days)
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-3" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <button 
                    type="button"
                    onclick="document.getElementById('createChannelModal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: var(--theme-background, white); color: var(--theme-gray-700, #374151); border: 1px solid var(--theme-gray-300, #D1D5DB);"
                    onmouseover="this.style.backgroundColor='var(--theme-gray-50, #F9FAFB)'"
                    onmouseout="this.style.backgroundColor='var(--theme-background, white)'"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: var(--theme-primary-600, #4F46E5); color: white;"
                    onmouseover="this.style.backgroundColor='var(--theme-primary-700, #4338CA)'"
                    onmouseout="this.style.backgroundColor='var(--theme-primary-600, #4F46E5)'"
                    id="submitCreateChannel"
                >
                    Create Channel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Renew Channel Modal -->
<div id="renewModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="renewModalTitle">
    <div class="rounded-lg shadow-xl max-w-md w-full mx-4" style="background-color: var(--theme-background, white);">
        <form id="renewForm" method="POST">
            @csrf
            <div class="px-6 py-4 border-b" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <h3 id="renewModalTitle" class="text-lg font-semibold" style="color: var(--theme-gray-900, #111827);">
                    Renew Channel
                </h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm mb-4" style="color: var(--theme-gray-600, #4B5563);">
                    Renew push notification channel: <span id="renewChannelId" class="font-mono font-semibold"></span>
                </p>
                <div>
                    <label for="renew_duration_hours" class="block text-sm font-medium mb-1" style="color: var(--theme-gray-700, #374151);">
                        Duration (hours) <span style="color: var(--theme-danger-600, #DC2626);" aria-hidden="true">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="renew_duration_hours" 
                        name="duration_hours" 
                        min="1" 
                        max="43200" 
                        value="24"
                        step="1"
                        required
                        aria-required="true"
                        aria-describedby="renew_duration_help"
                        class="w-full px-3 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                        style="border-color: var(--theme-gray-300, #D1D5DB); color: var(--theme-gray-900, #111827);"
                        onfocus="this.style.borderColor='var(--theme-primary-500, #6366F1)'; this.style.boxShadow='0 0 0 3px var(--theme-primary-100, #E0E7FF)'"
                        onblur="this.style.borderColor='var(--theme-gray-300, #D1D5DB)'; this.style.boxShadow='none'"
                    />
                    <p id="renew_duration_help" class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        New channel lifetime (maximum 43,200 hours / 30 days)
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-3" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <button 
                    type="button"
                    onclick="closeRenewModal()"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: var(--theme-background, white); color: var(--theme-gray-700, #374151); border: 1px solid var(--theme-gray-300, #D1D5DB);"
                    onmouseover="this.style.backgroundColor='var(--theme-gray-50, #F9FAFB)'"
                    onmouseout="this.style.backgroundColor='var(--theme-background, white)'"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: var(--theme-success-600, #059669); color: white;"
                    onmouseover="this.style.backgroundColor='var(--theme-success-700, #047857)'"
                    onmouseout="this.style.backgroundColor='var(--theme-success-600, #059669)'"
                    id="submitRenew"
                >
                    Renew Channel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test Result Modal -->
<div id="testResultModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="testResultTitle">
    <div class="rounded-lg shadow-xl max-w-lg w-full mx-4" style="background-color: var(--theme-background, white);">
        <div class="px-6 py-4 border-b" style="border-color: var(--theme-gray-200, #E5E7EB);">
            <h3 id="testResultTitle" class="text-lg font-semibold" style="color: var(--theme-gray-900, #111827);">
                Webhook Test Result
            </h3>
        </div>
        <div class="px-6 py-4">
            <div id="testResultContent"></div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end" style="border-color: var(--theme-gray-200, #E5E7EB);">
            <button 
                type="button"
                onclick="document.getElementById('testResultModal').classList.add('hidden')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: var(--theme-primary-600, #4F46E5); color: white;"
                onmouseover="this.style.backgroundColor='var(--theme-primary-700, #4338CA)'"
                onmouseout="this.style.backgroundColor='var(--theme-primary-600, #4F46E5)'"
            >
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openRenewModal(channelId, channelIdText) {
    document.getElementById('renewForm').action = `/admin/webhooks/${channelId}/renew`;
    document.getElementById('renewChannelId').textContent = channelIdText;
    document.getElementById('renewModal').classList.remove('hidden');
}

function closeRenewModal() {
    document.getElementById('renewModal').classList.add('hidden');
}

function confirmStop(channelId, channelIdText) {
    if (confirm(`Are you sure you want to stop channel ${channelIdText}? This will deactivate push notifications.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/webhooks/${channelId}/stop`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

async function testChannel(channelId, channelIdText) {
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    try {
        const response = await fetch(`/admin/webhooks/${channelId}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();
        
        const content = result.success 
            ? `<div class="space-y-3">
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2" style="color: var(--theme-success-600, #059669);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium" style="color: var(--theme-success-600, #059669);">Test Successful</span>
                </div>
                <div class="text-sm" style="color: var(--theme-gray-600, #4B5563);">
                    <p><strong>Message:</strong> ${result.message}</p>
                    <p><strong>Webhook URL:</strong> ${result.webhook_url}</p>
                    <p><strong>Response Code:</strong> ${result.response_code}</p>
                    <p><strong>Response Time:</strong> ${result.response_time_ms}ms</p>
                </div>
            </div>`
            : `<div class="space-y-3">
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2" style="color: var(--theme-danger-600, #DC2626);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium" style="color: var(--theme-danger-600, #DC2626);">Test Failed</span>
                </div>
                <p class="text-sm" style="color: var(--theme-gray-600, #4B5563);">${result.message}</p>
            </div>`;

        document.getElementById('testResultContent').innerHTML = content;
        document.getElementById('testResultModal').classList.remove('hidden');
    } catch (error) {
        alert('Failed to test webhook: ' + error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = originalContent;
    }
}

// Form submission loading states
document.getElementById('createChannelForm').addEventListener('submit', function() {
    const button = document.getElementById('submitCreateChannel');
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Creating...';
});

document.getElementById('renewForm').addEventListener('submit', function() {
    const button = document.getElementById('submitRenew');
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Renewing...';
});
</script>
@endsection
