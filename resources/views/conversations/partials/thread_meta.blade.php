{{-- Thread Meta: Metadata display for thread information (sender, time, recipients, etc.) --}}
<div class="thread-meta">
    <div class="thread-meta-row">
        {{-- Thread sender/creator --}}
        <div class="thread-meta-item thread-meta-from">
            <strong class="thread-meta-label">{{ __('From') }}:</strong>
            <span class="thread-meta-value">
                @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                    @if ($thread->customer_cached)
                        {{ $thread->customer_cached->getFullName(true) }}
                    @else
                        {{ $thread->getFromName() }}
                    @endif
                    <span class="text-help">&lt;{{ $thread->getFromEmail() }}&gt;</span>
                @elseif ($thread->type == App\Thread::TYPE_NOTE)
                    @if ($thread->created_by_user)
                        {{ $thread->created_by_user->getFullName() }}
                    @else
                        {{ __('Note') }}
                    @endif
                @else
                    @if ($thread->created_by_user)
                        {{ $thread->created_by_user->getFullName() }}
                    @endif
                @endif
            </span>
        </div>
        
        {{-- Thread date/time --}}
        <div class="thread-meta-item thread-meta-date">
            <strong class="thread-meta-label">{{ __('Date') }}:</strong>
            <span class="thread-meta-value">
                <a href="#thread-{{ $thread->id }}" 
                   class="thread-date" 
                   data-toggle="tooltip" 
                   title="{{ App\User::dateFormat($thread->created_at) }}">
                    {{ App\User::dateDiffForHumans($thread->created_at) }}
                </a>
            </span>
        </div>
    </div>
    
    {{-- Recipients (To, CC, BCC) for sent messages --}}
    @if ($thread->type == App\Thread::TYPE_MESSAGE && !$thread->isDraft())
        @if ($thread->getToArray())
            <div class="thread-meta-row">
                <div class="thread-meta-item thread-meta-to">
                    <strong class="thread-meta-label">{{ __('To') }}:</strong>
                    <span class="thread-meta-value">
                        {{ implode(', ', $thread->getToArray()) }}
                    </span>
                </div>
            </div>
        @endif
        
        @if ($thread->getCcArray())
            <div class="thread-meta-row">
                <div class="thread-meta-item thread-meta-cc">
                    <strong class="thread-meta-label">{{ __('CC') }}:</strong>
                    <span class="thread-meta-value text-help">
                        {{ implode(', ', $thread->getCcArray()) }}
                    </span>
                </div>
            </div>
        @endif
        
        @if ($thread->getBccArray())
            <div class="thread-meta-row">
                <div class="thread-meta-item thread-meta-bcc">
                    <strong class="thread-meta-label">{{ __('BCC') }}:</strong>
                    <span class="thread-meta-value text-help">
                        {{ implode(', ', $thread->getBccArray()) }}
                    </span>
                </div>
            </div>
        @endif
    @endif
    
    {{-- Thread status information --}}
    @if ($thread->state != App\Thread::STATE_PUBLISHED)
        <div class="thread-meta-row">
            <div class="thread-meta-item thread-meta-state">
                <strong class="thread-meta-label">{{ __('Status') }}:</strong>
                <span class="thread-meta-value">
                    <span class="label label-default">{{ $thread->getStateName() }}</span>
                </span>
            </div>
        </div>
    @endif
    
    {{-- Additional metadata via action hook --}}
    @action('thread.meta.after', $thread, $conversation ?? null, $mailbox ?? null)
</div>
