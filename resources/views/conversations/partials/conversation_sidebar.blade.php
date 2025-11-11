{{-- Conversation Sidebar: Metadata display for conversation details --}}
@if (!empty($conversation))
    <div class="conv-sidebar-container">
        <div class="conv-sidebar-header">
            <h4>{{ __('Conversation Details') }}</h4>
        </div>
        
        <div class="conv-sidebar-section">
            <div class="sidebar-item">
                <label>{{ __('Status') }}</label>
                <div class="sidebar-value">
                    <span class="badge badge-{{ App\Conversation::$status_classes[$conversation->getStatus()] ?? 'default' }}">
                        {{ $conversation->getStatusName() }}
                    </span>
                </div>
            </div>
            
            @if ($conversation->user_id)
                <div class="sidebar-item">
                    <label>{{ __('Assigned To') }}</label>
                    <div class="sidebar-value">
                        {{ $conversation->getAssigneeName(true) }}
                    </div>
                </div>
            @endif
            
            <div class="sidebar-item">
                <label>{{ __('Created') }}</label>
                <div class="sidebar-value">
                    <span data-toggle="tooltip" title="{{ App\User::dateFormat($conversation->created_at) }}">
                        {{ App\User::dateDiffForHumans($conversation->created_at) }}
                    </span>
                </div>
            </div>
            
            <div class="sidebar-item">
                <label>{{ __('Last Updated') }}</label>
                <div class="sidebar-value">
                    <span data-toggle="tooltip" title="{{ App\User::dateFormat($conversation->updated_at) }}">
                        {{ App\User::dateDiffForHumans($conversation->updated_at) }}
                    </span>
                </div>
            </div>
            
            @if ($conversation->closed_at)
                <div class="sidebar-item">
                    <label>{{ __('Closed') }}</label>
                    <div class="sidebar-value">
                        <span data-toggle="tooltip" title="{{ App\User::dateFormat($conversation->closed_at) }}">
                            {{ App\User::dateDiffForHumans($conversation->closed_at) }}
                        </span>
                    </div>
                </div>
            @endif
            
            <div class="sidebar-item">
                <label>{{ __('Mailbox') }}</label>
                <div class="sidebar-value">
                    @if (!empty($mailbox))
                        <a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}">
                            {{ $mailbox->name }}
                        </a>
                    @endif
                </div>
            </div>
            
            @if ($conversation->isPhone())
                <div class="sidebar-item">
                    <label>{{ __('Type') }}</label>
                    <div class="sidebar-value">
                        <i class="glyphicon glyphicon-earphone"></i> {{ __('Phone') }}
                    </div>
                </div>
            @endif
            
            @if ($conversation->isChat())
                <div class="sidebar-item">
                    <label>{{ __('Type') }}</label>
                    <div class="sidebar-value">
                        <i class="glyphicon glyphicon-phone"></i> {{ __('Chat') }}
                    </div>
                </div>
            @endif
            
            @action('conversation.sidebar.details', $conversation, $mailbox ?? null)
        </div>
        
        @action('conversation.sidebar.after_details', $conversation, $mailbox ?? null)
    </div>
@endif
