@props(['status', 'text' => null, 'alpineText' => null])

@php
$colors = [
    'success' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'completed' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'synced' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'active' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'approved' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'paid' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    
    'processing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'migrating' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'syncing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'running' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'pre-staging' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'scanning' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'analyzing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'sent' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    
    'warning' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'pending' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'paused' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'throttled' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'pending_renewal' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'delta_syncing' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700',
    'delta' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900 dark:text-purple-200 dark:border-purple-700',
    'initial' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700',
    
    'danger' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'failed' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'error' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'stopped' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'terminated' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'rejected' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'overdue' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    
    'neutral' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
    'draft' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
    'expired' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
    'cancelled' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
];

// Default to neutral if status not found, or try to match by key existence
$colorClass = $colors[$status] ?? $colors['neutral'];

// If status is not in keys, try to map generic terms
if (!isset($colors[$status])) {
    if (str_contains($status, 'fail') || str_contains($status, 'error') || str_contains($status, 'terminate') || str_contains($status, 'reject')) $colorClass = $colors['danger'];
    elseif (str_contains($status, 'warn') || str_contains($status, 'wait') || str_contains($status, 'pend')) $colorClass = $colors['warning'];
    elseif (str_contains($status, 'success') || str_contains($status, 'complete') || str_contains($status, 'active') || str_contains($status, 'paid')) $colorClass = $colors['success'];
    elseif (str_contains($status, 'process') || str_contains($status, 'run') || str_contains($status, 'send') || str_contains($status, 'sent')) $colorClass = $colors['processing'];
}

$displayText = $text ?? ucfirst(str_replace('_', ' ', $status));

// Animation Logic
$isPulsing = in_array($status, ['processing', 'migrating', 'syncing', 'running', 'scanning', 'analyzing']);
$isSteady = in_array($status, ['pending', 'paused', 'throttled', 'pre-staging', 'delta_syncing']);
@endphp

<span 
    @if($alpineText) x-text="{{ $alpineText }}" @endif
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $colorClass }} {{ $isPulsing ? 'animate-pulse' : '' }}">
    @if($isPulsing)
        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    @if($isSteady)
        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-current" fill="currentColor" viewBox="0 0 8 8">
            <circle cx="4" cy="4" r="3" />
        </svg>
    @endif
    {{ $displayText }}
</span>
