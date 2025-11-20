-- Please reply above this line --

@php
$createdBy = $thread->getCreatedBy();
$personName = $createdBy ? $createdBy->getFullName(true) : __('Unknown');
@endphp
@if (count($threads) == 1){{ __('Received a new conversation') }}@else @if ($thread->action_type == \App\Models\Thread::ACTION_TYPE_STATUS_CHANGED){{ __(":person marked as :status conversation", ['person' => $personName, 'status' => $thread->getStatusName()]) }}@elseif ($thread->action_type == \App\Models\Thread::ACTION_TYPE_USER_CHANGED){{ $personName }} {{ __("assigned to :person conversation", ['person' => $thread->getAssigneeName(false, $user)]) }}@elseif ($thread->type == \App\Models\Thread::TYPE_NOTE){{ __(":person added a note to conversation", ['person' => $personName]) }}@else{{ __(":person replied to conversation", ['person' => $personName]) }}@endif @endif #{{ $conversation->number }}

@foreach ($threads as $thread)
-----------------------------------------------------------
@php
$createdBy = $thread->getCreatedBy();
$personName = $createdBy ? $createdBy->getFullName(true) : __('Unknown');
@endphp
@if ($thread->type == \App\Models\Thread::TYPE_LINEITEM)
## {!! $thread->getActionText('', true, false, $user, $personName) !!}, {{ __('on :date', ['date' => \App\Models\User::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}
@else
@if ($thread->type == \App\Models\Thread::TYPE_NOTE)
## {{ __(':person added a note', ['person' => $personName]) }}, {{ __('on :date', ['date' => \App\Models\User::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@else
## @if ($loop->last){{ __(':person started the conversation', ['person' => $personName]) }}@else{{ __(':person replied', ['person' => $personName]) }}@endif, {{ __('on :date', ['date' => \App\Models\User::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@endif:
{!! strip_tags($thread->body) !!}
@endif
@if ($thread->has_attachments)
{{ __('Attached:') }}
@foreach ($thread->attachments as $i => $attachment)
{{ ($i+1) }}) {{ $attachment->file_name }} [{{ $attachment->url() }}]
@endforeach
@endif
@endforeach

{{ __('Conversation URL') }}: {{ $conversation->url() }}

-----------------------------------------------------------

{{ $mailbox->name }}:
{{ $mailbox->url() }}
