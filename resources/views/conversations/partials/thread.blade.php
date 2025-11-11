{{-- Thread Partial - Displays a single thread in a conversation --}}
@php
    $is_customer = $thread->type == 1; // TYPE_CUSTOMER
    $is_note = $thread->type == 2; // TYPE_NOTE
    $is_draft = $thread->state == 1; // STATE_DRAFT
    $thread_person = $is_customer ? $thread->customer : $thread->user;
@endphp

<div class="thread border border-gray-200 rounded-lg p-4 mb-4 {{ $is_note ? 'bg-yellow-50' : 'bg-white' }} {{ $is_draft ? 'border-dashed border-orange-400' : '' }}" 
     id="thread-{{ $thread->id }}" 
     data-thread-id="{{ $thread->id }}">
    
    <div class="flex items-start gap-4">
        {{-- Avatar --}}
        <div class="flex-shrink-0">
            @if($thread_person)
                <div class="w-10 h-10 rounded-full {{ $is_customer ? 'bg-gray-400' : 'bg-blue-600' }} flex items-center justify-center text-white font-semibold">
                    @if($thread_person->first_name)
                        {{ substr($thread_person->first_name, 0, 1) }}{{ substr($thread_person->last_name ?? '', 0, 1) }}
                    @else
                        {{ substr($thread_person->email ?? '?', 0, 1) }}
                    @endif
                </div>
            @else
                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold">
                    ?
                </div>
            @endif
        </div>

        {{-- Thread Content --}}
        <div class="flex-1 min-w-0">
            {{-- Thread Header --}}
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-gray-900">
                            @if($thread_person)
                                @if($is_customer && !empty($thread_person->url))
                                    <a href="{{ $thread_person->url() }}" class="text-blue-600 hover:underline">
                                        {{ $thread_person->getFullName(true) }}
                                    </a>
                                @else
                                    {{ $thread_person->getFullName() }}
                                @endif
                            @else
                                {{ __('Unknown') }}
                            @endif
                        </span>
                        
                        @if($is_note)
                            <span class="px-2 py-0.5 text-xs font-medium bg-yellow-200 text-yellow-800 rounded">
                                {{ __('Note') }}
                            </span>
                        @endif
                        
                        @if($is_draft)
                            <span class="px-2 py-0.5 text-xs font-medium bg-orange-200 text-orange-800 rounded">
                                {{ __('Draft') }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <a href="#thread-{{ $thread->id }}" class="hover:text-gray-700" 
                           title="{{ $thread->created_at->format('M d, Y g:i A') }}">
                            {{ $thread->created_at->diffForHumans() }}
                        </a>
                        
                        @if($thread->user_id && $thread->user)
                            <span class="mx-1">•</span>
                            <span>{{ $thread->user->getFullName() }}</span>
                        @endif
                        
                        @if($thread->status)
                            <span class="mx-1">•</span>
                            <span>{{ $thread->getStatusName() }}</span>
                        @endif
                    </div>

                    {{-- Recipients (To, Cc, Bcc) --}}
                    @if(!$is_note && ($thread->to || $thread->cc || $thread->bcc))
                        <div class="text-xs text-gray-600 mt-2 space-y-1">
                            @if($thread->to)
                                <div>
                                    <span class="font-medium">{{ __('To') }}:</span>
                                    {{ is_array($thread->to) ? implode(', ', $thread->to) : $thread->to }}
                                </div>
                            @endif
                            @if($thread->cc)
                                <div>
                                    <span class="font-medium">{{ __('Cc') }}:</span>
                                    {{ is_array($thread->cc) ? implode(', ', $thread->cc) : $thread->cc }}
                                </div>
                            @endif
                            @if($thread->bcc)
                                <div>
                                    <span class="font-medium">{{ __('Bcc') }}:</span>
                                    {{ is_array($thread->bcc) ? implode(', ', $thread->bcc) : $thread->bcc }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Thread Actions Dropdown --}}
                @if(!$is_draft)
                    <div class="flex-shrink-0" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="text-gray-400 hover:text-gray-600 p-1 rounded"
                                type="button">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10"
                             style="display: none;">
                            <div class="py-1">
                                @can('update', $conversation)
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ __('Edit') }}
                                    </a>
                                @endcan
                                @can('delete', $conversation)
                                    <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        {{ __('Delete') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Draft Actions --}}
            @if($is_draft)
                <div class="flex gap-2 mb-3">
                    <button class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 edit-draft-trigger">
                        {{ __('Edit') }}
                    </button>
                    <button class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 discard-draft-trigger">
                        {{ __('Discard') }}
                    </button>
                </div>
            @endif

            {{-- Thread Body --}}
            <div class="prose max-w-none text-gray-900 thread-body">
                {!! $thread->body ? nl2br(e($thread->body)) : '' !!}
            </div>

            {{-- Attachments --}}
            @if($thread->has_attachments && $thread->attachments && $thread->attachments->count() > 0)
                @include('conversations.partials.thread_attachments', ['attachments' => $thread->attachments])
            @endif
        </div>
    </div>
</div>
