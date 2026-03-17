@extends('layouts.app')

@section('title', 'Renew Webhook Channel')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold" style="color: var(--theme-gray-900, #111827);">
                        Renew Webhook Channel
                    </h1>
                    <p class="mt-1 text-sm" style="color: var(--theme-gray-600, #4B5563);">
                        Extend the expiration time for this push notification channel
                    </p>
                </div>
                <a href="{{ route('webhooks.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                   style="background-color: var(--theme-gray-200, #E5E7EB); color: var(--theme-gray-700, #374151);">
                    Back to Gateway
                </a>
            </div>
        </div>

        <!-- Channel Info Card -->
        <div class="rounded-lg shadow-sm border p-6 mb-6" style="background-color: var(--theme-background, white); border-color: var(--theme-gray-200, #E5E7EB);">
            <h2 class="text-lg font-medium mb-4" style="color: var(--theme-gray-900, #111827);">Channel Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Resource Type</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $channel->resource_type }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Channel ID</h3>
                    <p class="mt-1 font-mono text-sm" style="color: var(--theme-gray-900, #111827);">{{ $channel->channel_id }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Status</h3>
                    <p class="mt-1">
                        @if($channel->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">Inactive</span>
                        @endif
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Current Expiration</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $channel->expiration_time->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Renew Form -->
        <div class="rounded-lg shadow-sm border p-6" style="background-color: var(--theme-background, white); border-color: var(--theme-gray-200, #E5E7EB);">
            <h2 class="text-lg font-medium mb-4" style="color: var(--theme-gray-900, #111827);">Renew Channel</h2>
            <form action="{{ route('webhooks.renew', $channel) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="duration_hours" class="block text-sm font-medium" style="color: var(--theme-gray-700, #374151);">
                        Duration (hours)
                    </label>
                    <input type="number" name="duration_hours" id="duration_hours" value="168" min="1" max="43200"
                           class="mt-1 block w-full rounded-md border-neutral-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                           placeholder="e.g. 168 for 7 days">
                    <p class="mt-1 text-xs" style="color: var(--theme-gray-500, #6B7280);">
                        Maximum: 43200 hours (30 days)
                    </p>
                    @error('duration_hours')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background-color: var(--theme-primary-600, #4F46E5); color: white;">
                        Renew Channel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
