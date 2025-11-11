{{-- Previous Conversations Short - Show recent conversations from same customer --}}
@php
    $customer_id = $customer_id ?? ($customer->id ?? null);
    $current_conversation_id = $current_conversation_id ?? ($conversation->id ?? null);
    $limit = $limit ?? 5;
    
    if ($customer_id) {
        $prev_conversations = \App\Models\Conversation::where('customer_id', $customer_id)
            ->when($current_conversation_id, function($query) use ($current_conversation_id) {
                return $query->where('id', '!=', $current_conversation_id);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    } else {
        $prev_conversations = collect([]);
    }
@endphp

@if($prev_conversations->count() > 0)
    <div class="prev-conversations-short">
        <div class="space-y-2">
            @foreach($prev_conversations as $prev_conv)
                <div class="border-l-2 border-gray-300 pl-3 py-1 hover:border-blue-500 transition">
                    <a href="{{ route('conversations.show', $prev_conv) }}" 
                       class="block group">
                        <div class="text-sm font-medium text-gray-900 group-hover:text-blue-600 truncate">
                            #{{ $prev_conv->number }} - {{ $prev_conv->subject }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            @include('conversations.partials.badges', ['conversation' => $prev_conv])
                            <span>•</span>
                            <span>{{ $prev_conv->created_at->format('M d, Y') }}</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        
        @if(isset($customer) && method_exists($customer, 'url'))
            <div class="mt-3 pt-3 border-t border-gray-200">
                <a href="{{ $customer->url() }}" 
                   class="text-sm text-blue-600 hover:underline">
                    {{ __('View all conversations') }} →
                </a>
            </div>
        @endif
    </div>
@else
    <div class="text-sm text-gray-500">
        {{ __('No other conversations') }}
    </div>
@endif
