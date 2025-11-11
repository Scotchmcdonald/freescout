{{-- Thread Actions: Action buttons and dropdown menu for individual threads --}}
<div class="dropdown thread-options">
    <span class="dropdown-toggle" 
          data-toggle="dropdown" 
          role="button" 
          aria-expanded="false" 
          aria-haspopup="true"
          aria-label="{{ __('Thread Actions') }}">
        <b class="caret"></b>
    </span>
    
    <ul class="dropdown-menu dropdown-menu-right" role="menu">
        @if ($thread->type == App\Thread::TYPE_MESSAGE || $thread->type == App\Thread::TYPE_NOTE)
            {{-- Edit thread option (for notes and messages) --}}
            @if (Auth::user()->can('update', $thread))
                <li>
                    <a href="#" class="thread-edit-trigger" data-thread_id="{{ $thread->id }}" role="menuitem">
                        <i class="glyphicon glyphicon-pencil"></i> {{ __('Edit') }}
                    </a>
                </li>
            @endif
            
            {{-- Copy link option --}}
            <li>
                <a href="#thread-{{ $thread->id }}" class="thread-link-copy" role="menuitem">
                    <i class="glyphicon glyphicon-link"></i> {{ __('Copy Link') }}
                </a>
            </li>
            
            {{-- Quote option (for replies) --}}
            @if ($thread->type == App\Thread::TYPE_MESSAGE)
                <li>
                    <a href="#" class="thread-quote-trigger" data-thread_id="{{ $thread->id }}" role="menuitem">
                        <i class="glyphicon glyphicon-comment"></i> {{ __('Quote') }}
                    </a>
                </li>
            @endif
        @endif
        
        @action('thread.menu', $thread)
        
        {{-- View outgoing emails (for admins) --}}
        @if (Auth::user()->isAdmin() && $thread->type == App\Thread::TYPE_MESSAGE)
            <li>
                <a href="{{ route('conversations.ajax_html', array_merge(['action' => 'send_log'], \Request::all(), ['thread_id' => $thread->id])) }}" 
                   title="{{ __('View outgoing emails') }}" 
                   data-trigger="modal" 
                   data-modal-title="{{ __('Outgoing Emails') }}" 
                   data-modal-size="lg"
                   role="menuitem">
                    <i class="glyphicon glyphicon-envelope"></i> {{ __('Outgoing Emails') }}
                </a>
            </li>
        @endif
        
        {{-- Show original (for customer messages) --}}
        @if (Auth::user()->isAdmin() && $thread->type == App\Thread::TYPE_CUSTOMER && $thread->has_attachments)
            <li>
                <a href="{{ route('conversations.ajax_html', array_merge(['action' => 'show_original'], \Request::all(), ['thread_id' => $thread->id])) }}" 
                   title="{{ __('Show Original') }}" 
                   data-trigger="modal" 
                   data-modal-title="{{ __('Original Message') }}" 
                   data-modal-size="lg"
                   role="menuitem">
                    <i class="glyphicon glyphicon-file"></i> {{ __('Show Original') }}
                </a>
            </li>
        @endif
        
        @action('thread.menu.append', $thread)
        
        {{-- Delete thread option (for admins) --}}
        @if (Auth::user()->isAdmin() && Auth::user()->can('delete', $thread))
            <li class="divider"></li>
            <li>
                <a href="#" class="thread-delete-trigger" data-thread_id="{{ $thread->id }}" role="menuitem">
                    <i class="glyphicon glyphicon-trash"></i> {{ __('Delete') }}
                </a>
            </li>
        @endif
    </ul>
</div>
