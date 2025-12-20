@props(['status', 'text' => null, 'alpineText' => null])

@php
$colors = [
    'success' => 'bg-success-50 text-success-700 border-success-200',
    'completed' => 'bg-success-50 text-success-700 border-success-200',
    'synced' => 'bg-success-50 text-success-700 border-success-200',
    'active' => 'bg-success-50 text-success-700 border-success-200',
    
    'processing' => 'bg-primary-50 text-primary-700 border-primary-200',
    'migrating' => 'bg-primary-50 text-primary-700 border-primary-200',
    'syncing' => 'bg-primary-50 text-primary-700 border-primary-200',
    'running' => 'bg-primary-50 text-primary-700 border-primary-200',
    'pre-staging' => 'bg-primary-50 text-primary-700 border-primary-200',
    'scanning' => 'bg-primary-50 text-primary-700 border-primary-200',
    'analyzing' => 'bg-primary-50 text-primary-700 border-primary-200',
    
    'warning' => 'bg-warning-50 text-warning-700 border-warning-200',
    'pending' => 'bg-warning-50 text-warning-700 border-warning-200',
    'paused' => 'bg-warning-50 text-warning-700 border-warning-200',
    'throttled' => 'bg-warning-50 text-warning-700 border-warning-200',
    'delta_syncing' => 'bg-blue-50 text-blue-700 border-blue-200', // Special case for delta
    'delta' => 'bg-purple-50 text-purple-700 border-purple-200',
    'initial' => 'bg-blue-50 text-blue-700 border-blue-200',
    
    'danger' => 'bg-danger-50 text-danger-700 border-danger-200',
    'failed' => 'bg-danger-50 text-danger-700 border-danger-200',
    'error' => 'bg-danger-50 text-danger-700 border-danger-200',
    'stopped' => 'bg-danger-50 text-danger-700 border-danger-200',
    
    'neutral' => 'bg-gray-50 text-gray-600 border-gray-200',
    'draft' => 'bg-gray-50 text-gray-600 border-gray-200',
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
