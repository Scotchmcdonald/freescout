{{-- Conversation Status Badges Component --}}
{{-- Usage: <x-conversation-badges :conversation="$conversation" /> --}}
{{-- Or: <x-conversation-badges :status="1" :state="0" /> --}}

@props([
    'conversation' => null,
    'status' => null,
    'state' => null,
])

@php
    $status = $status ?? ($conversation?->status ?? null);
    $state = $state ?? ($conversation?->state ?? null);
    
    // Status constants (from Conversation model)
    $statusClasses = [
        1 => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)]', // Active
        2 => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)]', // Pending
        3 => 'bg-[var(--theme-bg-hover)] text-[var(--theme-text-muted)]', // Closed
        4 => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)]', // Spam
    ];
    
    $statusLabels = [
        1 => __('Active'),
        2 => __('Pending'),
        3 => __('Closed'),
        4 => __('Spam'),
    ];
@endphp

@if($status)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$status] ?? 'bg-[var(--theme-bg-hover)] text-[var(--theme-text-muted)]' }}"
          title="{{ $statusLabels[$status] ?? __('Unknown') }}">
        {{ $statusLabels[$status] ?? __('Unknown') }}
    </span>
@endif

@if($state == 1)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] ml-2"
          title="{{ __('Draft') }}">
        {{ __('Draft') }}
    </span>
@endif

@if($conversation?->has_attachments)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] ml-2"
          title="{{ __('Has Attachments') }}">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
        </svg>
        {{ __('Attachments') }}
    </span>
@endif
