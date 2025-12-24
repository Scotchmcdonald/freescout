@props(['status', 'text' => null, 'alpineText' => null])

@php
$colors = [
    'success' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'completed' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'synced' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    'active' => 'bg-[var(--theme-status-success-bg)] text-[var(--theme-status-success-text)] border-[var(--theme-status-success-bg)]',
    
    'processing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'migrating' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'syncing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'running' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'pre-staging' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'scanning' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    'analyzing' => 'bg-[var(--theme-status-info-bg)] text-[var(--theme-status-info-text)] border-[var(--theme-status-info-bg)]',
    
    'warning' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'pending' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'paused' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'throttled' => 'bg-[var(--theme-status-warning-bg)] text-[var(--theme-status-warning-text)] border-[var(--theme-status-warning-bg)]',
    'delta_syncing' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700', // Special case for delta
    'delta' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900 dark:text-purple-200 dark:border-purple-700',
    'initial' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700',
    
    'danger' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'failed' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'error' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    'stopped' => 'bg-[var(--theme-status-error-bg)] text-[var(--theme-status-error-text)] border-[var(--theme-status-error-bg)]',
    
    'neutral' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
    'draft' => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
];

// Default to neutral if status not found, or try to match by key existence
$colorClass = $colors[$status] ?? $colors['neutral'];

// If status is not in keys, try to map generic terms
if (!isset($colors[$status])) {
    if (str_contains($status, 'fail') || str_contains($status, 'error')) $colorClass = $colors['danger'];
    elseif (str_contains($status, 'warn') || str_contains($status, 'wait')) $colorClass = $colors['warning'];
    elseif (str_contains($status, 'success') || str_contains($status, 'complete')) $colorClass = $colors['success'];
    elseif (str_contains($status, 'process') || str_contains($status, 'run')) $colorClass = $colors['processing'];
}

$displayText = $text ?? ucfirst(str_replace('_', ' ', $status));

// Animation Logic
$isPulsing = in_array($status, ['processing', 'migrating', 'syncing', 'running', 'scanning', 'analyzing']);
$isSteady = in_array($status, ['pending', 'paused', 'throttled', 'pre-staging', 'delta_syncing']);
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }} border shadow-sm">
    @if($isPulsing)
        <span class="flex h-2 w-2 relative mr-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 bg-current"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-current"></span>
        </span>
    @elseif($isSteady)
        <span class="flex h-2 w-2 relative mr-2">
             <span class="absolute inline-flex h-full w-full rounded-full opacity-25 bg-current animate-pulse"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-current"></span>
        </span>
    @elseif(str_contains($colorClass, 'danger'))
        <svg class="mr-1.5 h-2 w-2 text-danger-500" fill="currentColor" viewBox="0 0 8 8">
            <circle cx="4" cy="4" r="3" />
        </svg>
    @elseif(str_contains($colorClass, 'success'))
        <svg class="mr-1.5 h-2 w-2 text-success-500" fill="currentColor" viewBox="0 0 8 8">
            <circle cx="4" cy="4" r="3" />
        </svg>
    @endif
    
    <span @if($alpineText) x-text="{{ $alpineText }}" @endif>{{ $displayText }}</span>
</span>
