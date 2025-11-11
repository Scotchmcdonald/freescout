{{-- Thread Attribution - Show "Created by" / "Replied by" attribution --}}
@php
    $thread = $thread ?? null;
    $show_avatar = $show_avatar ?? true;
    $show_tooltip = $show_tooltip ?? true;
    
    if ($thread) {
        $is_customer = $thread->type == 1; // TYPE_CUSTOMER
        $person = $is_customer ? $thread->customer : $thread->user;
        $created_by = $thread->created_by_user_id ? \App\Models\User::find($thread->created_by_user_id) : null;
    } else {
        $person = null;
        $created_by = null;
    }
@endphp

@if($person)
    <span class="thread-by inline-flex items-center gap-2">
        @if($show_avatar)
            <span class="flex-shrink-0">
                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full {{ $is_customer ? 'bg-gray-400' : 'bg-blue-600' }} text-white text-xs font-semibold"
                      @if($show_tooltip) 
                          title="{{ $person->getFullName() }}" 
                      @endif>
                    {{ substr($person->first_name ?? '', 0, 1) }}{{ substr($person->last_name ?? '', 0, 1) }}
                </span>
            </span>
        @endif
        
        <span class="text-sm"
              @if($show_tooltip) 
                  title="{{ $person->getFullName() }} ({{ $person->email ?? '' }})" 
              @endif>
            {{ $person->getFullName() }}
        </span>
        
        @if($created_by && $created_by->id != $person->id)
            <span class="text-xs text-gray-500"
                  @if($show_tooltip) 
                      title="{{ __('Created by :name', ['name' => $created_by->getFullName()]) }}" 
                  @endif>
                {{ __('via :name', ['name' => $created_by->getFullName()]) }}
            </span>
        @endif
    </span>
@else
    <span class="thread-by text-sm text-gray-500">
        {{ __('Unknown') }}
    </span>
@endif
