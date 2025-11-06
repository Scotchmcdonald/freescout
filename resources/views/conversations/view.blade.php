@extends('layouts.app')

@php
    $is_in_chat_mode = $conversation->isInChatMode();
@endphp

@section('title_full', '#'.$conversation->number.' '.$conversation->getSubject().($customer ? ' - '.$customer->getFullName(true) : ''))

@if (app('request')->input('print'))
    @section('body_class', 'body-conv print')
@else
    @section('body_class', 'body-conv'.($is_in_chat_mode ? ' chat-mode' : ''))
@endif

@section('body_attrs')
    @parent 
    data-conversation_id="{{ $conversation->id }}"
@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    <div id="conv-layout" class="flex gap-4 conv-type-{{ strtolower($conversation->getTypeName()) }} @if ($is_following) conv-following @endif" x-data="conversationManager()">
        <div id="conv-layout-header" class="bg-white shadow-sm rounded-lg p-4 mb-4">
            <div id="conv-toolbar" class="flex justify-between items-center mb-4">
                <div class="conv-actions flex items-center gap-2">
                    @php
                        $actions = \App\Misc\ConversationActionButtons::getActions($conversation, Auth::user(), $mailbox);
                        $toolbar_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_TOOLBAR);
                        $dropdown_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_DROPDOWN);
                    @endphp

                    @foreach ($toolbar_actions as $action_key => $action)
                        @if ($action_key === 'delete')
                            <button type="button" 
                                    class="editor-btn {{ $action['class'] }}" 
                                    data-toggle="tooltip"
                                    title="{{ $action['label'] }}"
                                    aria-label="{{ $action['label'] }}">
                                <i class="glyphicon {{ $action['icon'] }}"></i>
                            </button>
                        @elseif (!empty($action['url']))
                            <a href="{{ $action['url']($conversation) }}"
                               class="editor-btn {{ $action['class'] }}"
                               @if (!empty($action['attrs']))
                                   @foreach ($action['attrs'] as $attr_key => $attr_value)
                                       {{ $attr_key }}="{{ $attr_value }}"
                                   @endforeach
                               @endif
                               data-toggle="tooltip"
                               title="{{ $action['label'] }}"
                               aria-label="{{ $action['label'] }}">
                                <i class="glyphicon {{ $action['icon'] }}"></i>
                            </a>
                        @else
                            <button type="button"
                                    class="editor-btn {{ $action['class'] }}"
                                    data-toggle="tooltip"
                                    title="{{ $action['label'] }}"
                                    aria-label="{{ $action['label'] }}"
                                    @if (!empty($action['attrs']))
                                        @foreach ($action['attrs'] as $attr_key => $attr_value)
                                            {{ $attr_key }}="{{ $attr_value }}"
                                        @endforeach
                                    @endif>
                                <i class="glyphicon {{ $action['icon'] }}"></i>
                            </button>
                        @endif
                    @endforeach

                    @action('conversation.action_buttons', $conversation, $mailbox)

                    {{-- More Actions Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" 
                                class="editor-btn" 
                                @click="open = !open"
                                data-toggle="tooltip"
                                title="{{ __('More Actions') }}">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             class="dropdown-menu"
                             x-transition>
                            @action('conversation.prepend_action_buttons', $conversation, $mailbox)
                            @foreach ($dropdown_actions as $action_key => $action)
                                @if ($action_key === 'delete_mobile')
                                    <a href="#" class="{{ $action['class'] }} md:hidden">
                                        <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                    </a>
                                @else
                                    @if (!empty($action['has_opposite']))
                                        <a href="#" class="{{ $action['class'] }} @if ($is_following) hidden @endif" data-follow-action="follow">
                                            <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                        </a>
                                        <a href="#" class="{{ $action['opposite']['class'] }} @if (!$is_following) hidden @endif" data-follow-action="unfollow">
                                            <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['opposite']['label'] }}
                                        </a>
                                    @else
                                        <a href="{{ !empty($action['url']) ? $action['url']($conversation) : '#' }}"
                                           class="{{ $action['class'] }}"
                                           @if (!empty($action['attrs']))
                                               @foreach ($action['attrs'] as $attr_key => $attr_value)
                                                   {{ $attr_key }}="{{ $attr_value }}"
                                               @endforeach
                                           @endif>
                                            <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                        </a>
                                    @endif
                                @endif
                            @endforeach
                            @action('conversation.append_action_buttons', $conversation, $mailbox)
                        </div>
                    </div>
                </div>

                <div class="conv-info flex items-center gap-4">
                    @action('conversation.convinfo.prepend', $conversation, $mailbox)
                    
                    @if ($conversation->state != App\Conversation::STATE_DELETED)
                        {{-- Assignee Dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" 
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
                                    data-toggle="tooltip"
                                    title="{{ __('Assignee') }}: {{ $conversation->getAssigneeName(true) }}">
                                <i class="glyphicon glyphicon-user"></i>
                                <span>{{ $conversation->getAssigneeName(true) }}</span>
                                <i class="glyphicon glyphicon-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="dropdown-menu max-h-64 overflow-y-auto"
                                 x-transition>
                                <a href="#" 
                                   class="@if (!$conversation->user_id) bg-blue-50 @endif"
                                   @click.prevent="window.conversationManager.changeAssignee(-1); open = false">
                                    {{ __("Anyone") }}
                                </a>
                                <a href="#" 
                                   class="@if ($conversation->user_id == Auth::user()->id) bg-blue-50 @endif"
                                   @click.prevent="window.conversationManager.changeAssignee({{ Auth::user()->id }}); open = false">
                                    {{ __("Me") }}
                                </a>
                                @foreach ($mailbox->usersAssignable() as $user)
                                    @if ($user->id != Auth::user()->id)
                                        <a href="#" 
                                           class="@if ($conversation->user_id == $user->id) bg-blue-50 @endif"
                                           @click.prevent="window.conversationManager.changeAssignee({{ $user->id }}); open = false">
                                            {{ $user->getFullName() }}@action('assignee_list.item_append', $user)
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Status Dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        @if ($conversation->state != App\Conversation::STATE_DELETED)
                            <button type="button" 
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 rounded {{ 'bg-' . App\Conversation::$status_classes[$conversation->getStatus()] . '-100' }}"
                                    data-toggle="tooltip"
                                    title="{{ __('Status') }}: {{ $conversation->getStatusName() }}">
                                <i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->getStatus()] }}"></i>
                                <span>{{ $conversation->getStatusName() }}</span>
                                <i class="glyphicon glyphicon-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="dropdown-menu"
                                 x-transition>
                                @if ($conversation->status != App\Conversation::STATUS_SPAM)
                                    @foreach (App\Conversation::$statuses as $status => $dummy)
                                        <a href="#" 
                                           class="@if ($conversation->status == $status) bg-blue-50 @endif"
                                           @click.prevent="window.conversationManager.changeStatus({{ $status }}); open = false">
                                            {{ App\Conversation::statusCodeToName($status) }}
                                        </a>
                                    @endforeach
                                @else
                                    <a href="#" @click.prevent="window.conversationManager.changeStatus('not_spam'); open = false">
                                        {{ __('Not Spam') }}
                                    </a>
                                @endif
                            </div>
                        @else
                            <button type="button" class="flex items-center gap-2 px-3 py-2 bg-gray-200 rounded">
                                <i class="glyphicon glyphicon-trash"></i>
                                <span>{{ __('Deleted') }}</span>
                            </button>
                        @endif
                    </div>

                    @action('conversation.convinfo.before_nav', $conversation, $mailbox)
                    
                    {{-- Navigation --}}
                    <div class="flex items-center gap-1">
                        <a href="{{ $conversation->urlPrev(App\Conversation::getFolderParam()) }}" 
                           class="editor-btn"
                           data-toggle="tooltip" 
                           title="{{ __('Newer') }}">
                            <i class="glyphicon glyphicon-chevron-left"></i>
                        </a>
                        <a href="{{ $conversation->urlNext(App\Conversation::getFolderParam()) }}" 
                           class="editor-btn"
                           data-toggle="tooltip" 
                           title="{{ __('Older') }}">
                            <i class="glyphicon glyphicon-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Subject Line --}}
            <div id="conv-subject" class="border-t pt-4">
                <div class="flex items-center justify-between" x-data="{ editing: false }">
                    <div class="flex-1">
                        <h1 class="text-2xl font-semibold" x-show="!editing">
                            {{ $conversation->getSubject() }}
                        </h1>
                        <div class="flex items-center gap-2" x-show="editing">
                            <input type="text" 
                                   x-ref="subjectInput"
                                   value="{{ $conversation->getSubject() }}" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" />
                            <button type="button" 
                                    @click="window.conversationManager.updateSubject($refs.subjectInput.value); editing = false"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="glyphicon glyphicon-ok"></i>
                            </button>
                            <button type="button" 
                                    @click="editing = false"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                <i class="glyphicon glyphicon-remove"></i>
                            </button>
                        </div>
                    </div>
                    
                    @if ($conversation->isChat() && $conversation->getChannelName())
                        <div class="flex items-center gap-2">
                            @if (\Helper::isChatMode())
                                <a class="status-badge status-badge-active" 
                                   href="{{ request()->fullUrlWithQuery(['chat_mode' => '0']) }}" 
                                   title="{{ __('Exit') }}"
                                   data-toggle="tooltip">
                                    <i class="glyphicon glyphicon-stop"></i> {{ __('Chat Mode') }}
                                </a>
                            @else
                                <a class="status-badge status-badge-pending" 
                                   href="{{ request()->fullUrlWithQuery(['chat_mode' => '1']) }}">
                                    <i class="glyphicon glyphicon-play"></i> {{ __('Chat Mode') }}
                                </a>
                            @endif
                            <span class="status-badge">
                                <i class="glyphicon glyphicon-phone"></i> {{ $conversation->getChannelName() }}
                            </span>
                        </div>
                    @endif
                    
                    @action('conversation.after_subject', $conversation, $mailbox)
                    
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="window.conversationManager.toggleStar()"
                                class="editor-btn"
                                title="@if ($conversation->isStarredByUser()){{ __('Unstar Conversation') }}@else{{ __('Star Conversation') }}@endif">
                            <i class="glyphicon @if ($conversation->isStarredByUser()) glyphicon-star @else glyphicon-star-empty @endif"></i>
                        </button>
                        <span class="text-sm text-gray-600">#<strong>{{ $conversation->number }}</strong></span>
                    </div>
                </div>

                {{-- Real-time Viewers --}}
                <div id="conv-viewers" class="mt-4" x-show="$store.viewers && $store.viewers.length > 0">
                    <template x-for="viewer in $store.viewers" :key="viewer.id">
                        <div class="viewer-item">
                            <img :src="viewer.avatar" 
                                 :alt="viewer.name"
                                 class="w-8 h-8 rounded-full" />
                            <span x-text="viewer.replying ? viewer.name + ' {{ __('is replying') }}' : viewer.name + ' {{ __('is viewing') }}'"></span>
                        </div>
                    </template>
                </div>

                @if ($is_in_chat_mode)
                    <div class="conv-top-chat mt-4 p-4 bg-blue-50 rounded">
                        @if ($conversation->user_id != Auth::user()->id)
                            <button type="button" 
                                    @click="window.conversationManager.acceptChat()"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                {{ __('Accept Chat') }}
                            </button>
                        @elseif (!$conversation->isClosed())
                            <button type="button" 
                                    @click="window.conversationManager.endChat()"
                                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                {{ __('End Chat') }}
                            </button>
                        @endif
                        <button type="button" 
                                @click="$refs.details.classList.toggle('hidden')"
                                class="ml-2 text-blue-600 hover:underline">
                            {{ __('Show Details') }}
                        </button>
                    </div>
                    <div x-ref="details" class="hidden">
                @endif

                @action('conversation.after_subject_block', $conversation, $mailbox)
                
                @if ($conversation->isInChatMode())
                    </div>
                @endif

                {{-- Reply Form --}}
                <div class="conv-action-wrapper mt-4" x-show="$store.showReplyForm">
                    <div class="conv-reply-block bg-white rounded-lg border border-gray-300 p-4">
                        <form class="form-reply" 
                              method="POST" 
                              action="{{ route('conversations.ajax_html', ['action' => 'send_reply']) }}"
                              @submit.prevent="window.conversationManager.submitReply($event)">
                            @csrf
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}" />
                            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}" />
                            <input type="hidden" name="saved_reply_id" value="" />
                            <input type="hidden" name="thread_id" value="" />
                            <input type="hidden" name="is_note" value="0" />
                            <input type="hidden" name="subtype" value="" />

                            {{-- From Alias --}}
                            @if (count($from_aliases))
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('From') }}</label>
                                    <select name="from_alias" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        @foreach ($from_aliases as $from_alias_email => $from_alias_name)
                                            <option value="@if ($from_alias_email != $mailbox->email){{ $from_alias_email }}@endif" @if (!empty($from_alias) && $from_alias == $from_alias_email)selected@endif>
                                                @if ($from_alias_name){{ $from_alias_email }} ({{ $from_alias_name }})@else{{ $from_alias_email }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- To Field --}}
                            @if (!empty($to_customers))
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('To') }}</label>
                                    <select name="to" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        @foreach ($to_customers as $to_customer)
                                            <option value="{{ $to_customer['email'] }}" @if ($to_customer['email'] == $conversation->customer_email)selected@endif>
                                                {{ $to_customer['customer']->getFullName(true) }} &lt;{{ $to_customer['email'] }}&gt;
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Cc/Bcc Toggle --}}
                            <div class="mb-4" x-data="{ showCc: {{ $cc ? 'true' : 'false' }}, showBcc: {{ $bcc ? 'true' : 'false' }} }">
                                <button type="button" 
                                        @click="showCc = !showCc"
                                        class="text-sm text-blue-600 hover:underline">
                                    {{ __('Cc/Bcc') }}
                                </button>
                                
                                <div x-show="showCc" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Cc') }}</label>
                                    <select name="cc[]" multiple class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        @if ($cc)
                                            @foreach ($cc as $cc_email)
                                                <option value="{{ $cc_email }}" selected>{{ $cc_email }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div x-show="showBcc" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Bcc') }}</label>
                                    <select name="bcc[]" multiple class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        @if ($bcc)
                                            @foreach ($bcc as $bcc_email)
                                                <option value="{{ $bcc_email }}" selected>{{ $bcc_email }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            {{-- Alert for switching to note --}}
                            @if (!empty($threads[0]) && $threads[0]->type == App\Thread::TYPE_NOTE && $threads[0]->created_by_user_id != Auth::user()->id && $threads[0]->created_by_user)
                                <div class="alert alert-warning mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <i class="glyphicon glyphicon-exclamation-sign"></i>
                                    {!! __('This reply will go to the customer. :%switch_start%Switch to a note:%switch_end% if you are replying to :user_name.', [
                                        '%switch_start%' => '<a href="#" class="text-blue-600 hover:underline switch-to-note">', 
                                        '%switch_end%' => '</a>', 
                                        'user_name' => htmlspecialchars($threads[0]->created_by_user->getFullName())
                                    ]) !!}
                                </div>
                            @endif

                            {{-- Attachments --}}
                            <div id="dropzone-area" class="mb-4"></div>

                            {{-- Rich Text Editor --}}
                            <div class="mb-4">
                                <div id="editor-container" 
                                     data-placeholder="@if ($conversation->isInChatMode()){{ __('Use ENTER to send the message and SHIFT+ENTER for a new line') }}@else{{ __('Type your reply...') }}@endif"></div>
                                <textarea name="body" id="body" class="hidden"></textarea>
                                @if ($errors->has('body'))
                                    <p class="form-error">{{ $errors->first('body') }}</p>
                                @endif
                            </div>

                            {{-- Bottom Toolbar --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        {{ __('Send Reply') }}
                                    </button>
                                    <button type="button" 
                                            @click="window.conversationManager.createNote()"
                                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                        {{ __('Add Note') }}
                                    </button>
                                    <button type="button" 
                                            @click="window.conversationManager.saveDraft()"
                                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                        {{ __('Save Draft') }}
                                    </button>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <span x-show="$store.draftSaved">{{ __('Draft saved') }}</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer Sidebar --}}
        <div id="conv-layout-customer" class="w-80 bg-white shadow-sm rounded-lg p-4">
            @include('conversations/partials/customer_sidebar')
            @action('conversation.after_customer_sidebar', $conversation)
        </div>

        {{-- Thread List --}}
        <div id="conv-layout-main" class="flex-1">
            @action('conversation.before_threads', $conversation)
            @include('conversations/partials/threads')
            @action('conversation.after_threads', $conversation)
        </div>
    </div>
@endsection

@section('body_bottom')
    @parent
    @include('conversations.partials.settings_modal', ['conversation' => $conversation])
@endsection

@section('javascript')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize conversation manager
            if (typeof ConversationManager !== 'undefined') {
                window.conversationManager = new ConversationManager({
                    conversationId: {{ $conversation->id }},
                    mailboxId: {{ $mailbox->id }},
                    editorSelector: '#editor-container',
                    uploaderSelector: '#dropzone-area'
                });
            }

            // Alpine.js store for reactive data
            if (typeof Alpine !== 'undefined') {
                Alpine.store('showReplyForm', false);
                Alpine.store('draftSaved', false);
                Alpine.store('viewers', []);
            }
        });
    </script>
@endsection
