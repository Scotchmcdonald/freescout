{{-- Status Badges Partial - Display status badges (active, closed, spam, etc.) --}}
@php
    $status = $status ?? ($conversation->status ?? null);
    $state = $state ?? ($conversation->state ?? null);
@endphp

@if($status)
    @php
        // Status constants (from Conversation model)
        $status_classes = [
            1 => 'bg-success-100 text-success-800', // Active
            2 => 'bg-warning-100 text-warning-800', // Pending
            3 => 'bg-neutral-100 text-neutral-800', // Closed
            4 => 'bg-danger-100 text-danger-800', // Spam
        ];
        
        $status_labels = [
            1 => __('Active'),
            2 => __('Pending'),
            3 => __('Closed'),
            4 => __('Spam'),
        ];
        
        $badge_class = $status_classes[$status] ?? 'bg-neutral-100 text-neutral-800';
        $badge_label = $status_labels[$status] ?? __('Unknown');
    @endphp
    
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge_class }}"
          title="{{ $badge_label }}">
        {{ $badge_label }}
    </span>
@endif

@if(isset($state) && $state == 1)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 ml-2"
          title="{{ __('Draft') }}">
        {{ __('Draft') }}
    </span>
@endif

@if(isset($conversation) && $conversation->has_attachments)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 ml-2"
          title="{{ __('Has Attachments') }}">
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
        </svg>
        {{ __('Attachments') }}
    </span>
@endif
