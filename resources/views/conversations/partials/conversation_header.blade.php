{{-- Conversation Header: Subject, Tags, Number, Star, and Viewers --}}
<div id="conv-subject">
    <div class="conv-subj-block">
        <div class="conv-subjwrap">
            <div class="conv-subjtext">
                <span>{{ $conversation->getSubject() }}</span>
                <div class="input-group input-group-lg conv-subj-editor">
                    <input type="text" id="conv-subj-value" class="form-control" value="{{ $conversation->getSubject() }}" aria-label="{{ __('Conversation Subject') }}" />
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="button" data-loading-text="…" aria-label="{{ __('Save Subject') }}"><i class="glyphicon glyphicon-ok"></i></button>
                    </span>
                </div>
            </div>
            @if ($conversation->isChat() && $conversation->getChannelName())
                <span class="conv-tags">
                    @if (\Helper::isChatMode())
                        <a class="btn btn-default fs-tag-btn" href="{{ request()->fullUrlWithQuery(['chat_mode' => '0']) }}" title="{{ __('Exit') }}" data-toggle="tooltip">
                            <small class="glyphicon glyphicon-stop"></small> {{ __('Chat Mode') }}
                        </a>
                    @else
                        <a class="btn btn-primary fs-tag-btn" href="{{ request()->fullUrlWithQuery(['chat_mode' => '1']) }}">
                            <small class="glyphicon glyphicon-play"></small> {{ __('Chat Mode') }}
                        </a>
                    @endif
                    <span class="fs-tag fs-tag-md">
                        <a class="fs-tag-name" href="#">
                            <small class="glyphicon glyphicon-phone"></small> {{ $conversation->getChannelName() }}
                        </a>
                    </span>
                </span>
            @endif
            @action('conversation.after_subject', $conversation, $mailbox)
            <div class="conv-numnav">
                <i class="glyphicon conv-star @if ($conversation->isStarredByUser()) glyphicon-star @else glyphicon-star-empty @endif" 
                   title="@if ($conversation->isStarredByUser()){{ __('Unstar Conversation') }}@else{{ __('Star Conversation') }}@endif"
                   role="button"
                   aria-label="@if ($conversation->isStarredByUser()){{ __('Unstar Conversation') }}@else{{ __('Star Conversation') }}@endif"></i>&nbsp; 
                # <strong>{{ $conversation->number }}</strong>
            </div>
            <div id="conv-viewers">
                @foreach ($viewers as $viewer)
                    <span class="photo-xs viewer-{{ $viewer['user']->id }} @if ($viewer['replying']) viewer-replying @endif" 
                          data-toggle="tooltip" 
                          title="@if ($viewer['replying']){{ __(':user is replying', ['user' => $viewer['user']->getFullName()]) }}@else{{ __(':user is viewing', ['user' => $viewer['user']->getFullName()]) }}@endif">
                        @include('partials/person_photo', ['person' => $viewer['user']])
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    @if ($is_in_chat_mode)
        <div class="conv-top-block conv-top-chat clearfix">
            @if ($conversation->user_id != Auth::user()->id)
                <button class="btn btn-success btn-xs pull-right chat-accept" data-loading-text="{{ __('Accept Chat') }}…">{{ __('Accept Chat') }}</button>
            @elseif (!$conversation->isClosed())
                <button class="btn btn-default btn-xs pull-right chat-end" data-loading-text="{{ __('End Chat') }}…">{{ __('End Chat') }}</button>
            @endif
            <a href="#conv-top-blocks" data-toggle="collapse">{{ __('Show Details') }} <b class="caret"></b></a>
        </div>
        <div class="collapse" id="conv-top-blocks">
    @endif
        @action('conversation.after_subject_block', $conversation, $mailbox)
    @if ($conversation->isInChatMode())
        </div>
    @endif
</div>
