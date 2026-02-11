@extends('layouts.app')

@section('title', 'Milestone Details')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold" style="color: var(--theme-gray-900, #111827);">
                        Milestone Details
                    </h1>
                    <p class="mt-1 text-sm" style="color: var(--theme-gray-600, #4B5563);">
                        {{ $milestone->title }}
                    </p>
                </div>
                <a href="{{ route('milestones.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                   style="background-color: var(--theme-gray-200, #E5E7EB); color: var(--theme-gray-700, #374151);">
                    Back to Milestones
                </a>
            </div>
        </div>

        <!-- Milestone Card -->
        <div class="rounded-lg shadow-sm border p-6" style="background-color: var(--theme-background, white); border-color: var(--theme-gray-200, #E5E7EB);">
            <!-- Status Badge -->
            <div class="flex items-center justify-between mb-6">
                @php $statusInfo = $milestone->getStatusInfo(); @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusInfo['color'] }}">
                    {{ $statusInfo['label'] }}
                </span>
                @if($milestone->isOverdue())
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        Overdue
                    </span>
                @endif
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Title</h3>
                    <p class="mt-1 text-lg font-semibold" style="color: var(--theme-gray-900, #111827);">{{ $milestone->title }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Status</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $statusInfo['label'] }}</p>
                </div>

                @if($milestone->description)
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Description</h3>
                    <p class="mt-1" style="color: var(--theme-gray-700, #374151);">{{ $milestone->description }}</p>
                </div>
                @endif

                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Progress</h3>
                    <div class="mt-2">
                        <div class="flex items-center">
                            <div class="flex-1 bg-gray-200 rounded-full h-2.5 mr-3">
                                <div class="h-2.5 rounded-full" style="width: {{ $milestone->progress_percentage }}%; background-color: var(--theme-primary-600, #4F46E5);"></div>
                            </div>
                            <span class="text-sm font-medium" style="color: var(--theme-gray-700, #374151);">{{ $milestone->progress_percentage }}%</span>
                        </div>
                    </div>
                </div>

                @if($milestone->target_date)
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Target Date</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $milestone->target_date->format('M d, Y') }}</p>
                </div>
                @endif

                @if($milestone->billing_amount)
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Billing Amount</h3>
                    <p class="mt-1 text-lg font-semibold" style="color: var(--theme-gray-900, #111827);">${{ number_format($milestone->billing_amount, 2) }}</p>
                </div>
                @endif

                @if($milestone->assignedUser)
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Assigned To</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $milestone->assignedUser->first_name }} {{ $milestone->assignedUser->last_name }}</p>
                </div>
                @endif

                @if($milestone->started_at)
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Started At</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $milestone->started_at->format('M d, Y H:i') }}</p>
                </div>
                @endif

                @if($milestone->completed_at)
                <div>
                    <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Completed At</h3>
                    <p class="mt-1" style="color: var(--theme-gray-900, #111827);">{{ $milestone->completed_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
            </div>

            @if($milestone->notes)
            <div class="mt-6 pt-6 border-t" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <h3 class="text-sm font-medium" style="color: var(--theme-gray-500, #6B7280);">Notes</h3>
                <p class="mt-1" style="color: var(--theme-gray-700, #374151);">{{ $milestone->notes }}</p>
            </div>
            @endif

            @if($milestone->blockers)
            <div class="mt-6 pt-6 border-t" style="border-color: var(--theme-gray-200, #E5E7EB);">
                <h3 class="text-sm font-medium text-red-600">Blockers</h3>
                <p class="mt-1 text-red-700">{{ $milestone->blockers }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
