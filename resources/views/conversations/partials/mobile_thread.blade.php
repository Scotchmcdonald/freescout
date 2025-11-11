{{-- Mobile-Optimized Thread Display: Compact thread view for mobile devices --}}
<div class="thread-mobile" id="thread-mobile-{{ $thread->id }}" data-thread_id="{{ $thread->id }}">
    <div class="thread-mobile-container">
        {{-- Thread Header with Avatar --}}
        <div class="thread-mobile-header">
            <div class="thread-mobile-avatar">
                @include('partials/person_photo', ['person' => $thread->getPerson(true)])
            </div>
            <div class="thread-mobile-meta">
                <div class="thread-mobile-sender">
                    @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                        @if ($thread->customer_cached)
                            {{ $thread->customer_cached->getFullName(true) }}
                        @else
                            {{ $thread->getFromName() }}
                        @endif
                    @elseif ($thread->type == App\Thread::TYPE_NOTE)
                        <span class="label label-info">{{ __('Note') }}</span>
                        @if ($thread->created_by_user)
                            {{ $thread->created_by_user->getFullName() }}
                        @endif
                    @else
                        @if ($thread->created_by_user)
                            {{ $thread->created_by_user->getFullName() }}
                        @endif
                    @endif
                </div>
                <div class="thread-mobile-date text-muted">
                    {{ App\User::dateDiffForHumans($thread->created_at) }}
                </div>
            </div>
            <div class="thread-mobile-actions">
                <button class="btn btn-link btn-sm thread-mobile-toggle" 
                        data-toggle="collapse" 
                        data-target="#thread-mobile-actions-{{ $thread->id }}"
                        aria-expanded="false"
                        aria-label="{{ __('Show actions') }}">
                    <i class="glyphicon glyphicon-option-vertical"></i>
                </button>
            </div>
        </div>
        
        {{-- Collapsible Actions Menu --}}
        <div class="collapse thread-mobile-actions-menu" id="thread-mobile-actions-{{ $thread->id }}">
            <div class="list-group">
                @if ($thread->type == App\Thread::TYPE_MESSAGE || $thread->type == App\Thread::TYPE_NOTE)
                    @if (Auth::user()->can('update', $thread))
                        <a href="#" class="list-group-item thread-edit-trigger" data-thread_id="{{ $thread->id }}">
                            <i class="glyphicon glyphicon-pencil"></i> {{ __('Edit') }}
                        </a>
                    @endif
                    
                    @if ($thread->type == App\Thread::TYPE_MESSAGE)
                        <a href="#" class="list-group-item thread-quote-trigger" data-thread_id="{{ $thread->id }}">
                            <i class="glyphicon glyphicon-comment"></i> {{ __('Quote') }}
                        </a>
                    @endif
                @endif
                
                <a href="#thread-mobile-{{ $thread->id }}" class="list-group-item thread-link-copy">
                    <i class="glyphicon glyphicon-link"></i> {{ __('Copy Link') }}
                </a>
                
                @if (Auth::user()->isAdmin() && Auth::user()->can('delete', $thread))
                    <a href="#" class="list-group-item thread-delete-trigger text-danger" data-thread_id="{{ $thread->id }}">
                        <i class="glyphicon glyphicon-trash"></i> {{ __('Delete') }}
                    </a>
                @endif
            </div>
        </div>
        
        {{-- Thread Body --}}
        <div class="thread-mobile-body">
            {!! $thread->getCleanBody() !!}
        </div>
        
        {{-- Thread Attachments --}}
        @if ($thread->has_attachments && !empty($thread->attachments))
            <div class="thread-mobile-attachments">
                <div class="thread-mobile-attachments-header">
                    <i class="glyphicon glyphicon-paperclip"></i>
                    <strong>{{ __('Attachments') }} ({{ count($thread->attachments) }})</strong>
                </div>
                <div class="thread-mobile-attachments-list">
                    @foreach ($thread->attachments as $attachment)
                        <div class="thread-mobile-attachment">
                            <div class="attachment-icon">
                                @if (strpos($attachment->mime_type, 'image/') === 0)
                                    <i class="glyphicon glyphicon-picture"></i>
                                @elseif (strpos($attachment->mime_type, 'application/pdf') === 0)
                                    <i class="glyphicon glyphicon-file"></i>
                                @else
                                    <i class="glyphicon glyphicon-paperclip"></i>
                                @endif
                            </div>
                            <div class="attachment-info">
                                <a href="{{ $attachment->url() }}" target="_blank" class="attachment-name">
                                    {{ $attachment->file_name }}
                                </a>
                                <div class="attachment-size text-muted">
                                    {{ $attachment->getSizeName() }}
                                </div>
                            </div>
                            <div class="attachment-download">
                                <a href="{{ $attachment->url() }}" download class="btn btn-link btn-sm">
                                    <i class="glyphicon glyphicon-download-alt"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        @action('thread.mobile.after', $thread, $conversation ?? null, $mailbox ?? null)
    </div>
</div>

<style>
    @media (max-width: 767px) {
        .thread-mobile {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin: 10px 0;
            background: #fff;
        }
        
        .thread-mobile-container {
            padding: 12px;
        }
        
        .thread-mobile-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .thread-mobile-avatar {
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .thread-mobile-meta {
            flex-grow: 1;
        }
        
        .thread-mobile-sender {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .thread-mobile-date {
            font-size: 12px;
        }
        
        .thread-mobile-actions {
            flex-shrink: 0;
        }
        
        .thread-mobile-actions-menu {
            margin: 10px -12px;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .thread-mobile-body {
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .thread-mobile-body img {
            max-width: 100%;
            height: auto;
        }
        
        .thread-mobile-attachments {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
        }
        
        .thread-mobile-attachments-header {
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .thread-mobile-attachment {
            display: flex;
            align-items: center;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        
        .attachment-icon {
            margin-right: 10px;
            font-size: 18px;
            color: #666;
        }
        
        .attachment-info {
            flex-grow: 1;
            min-width: 0;
        }
        
        .attachment-name {
            font-size: 13px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .attachment-size {
            font-size: 11px;
        }
        
        .attachment-download {
            flex-shrink: 0;
        }
    }
</style>
