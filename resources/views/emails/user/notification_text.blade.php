-- Please reply above this line --

@if (count($threads) == 1){{ __('Received a new conversation') }}@else @if ($thread->action_type == \App\Models\Thread::ACTION_TYPE_STATUS_CHANGED){{ __(":person marked as :status conversation", ['person' => $thread->getCreatedBy()->getFullName(true), 'status' => $thread->getStatusName()]) }}@elseif ($thread->action_type == \App\Models\Thread::ACTION_TYPE_USER_CHANGED){{ $thread->getCreatedBy()->getFullName(true) }} {{ __("assigned to :person conversation", ['person' => $thread->getAssigneeName(false, $user)]) }}@elseif ($thread->type == \App\Models\Thread::TYPE_NOTE){{ __(":person added a note to conversation", ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@else{{ __(":person replied to conversation", ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@endif @endif #{{ $conversation->number }}

@foreach ($threads as $thread)
-----------------------------------------------------------
@if ($thread->type == \App\Models\Thread::TYPE_LINEITEM)
## {!! $thread->getActionText('', true, false, $user, $thread->getCreatedBy()->getFullName(true)) !!}, {{ __('on :date', ['date' => \App\Models\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}
@else
@if ($thread->type == \App\Models\Thread::TYPE_NOTE)
## {{ __(':person added a note', ['person' => $thread->getCreatedBy()->getFullName(true)]) }}, {{ __('on :date', ['date' => \App\Models\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@else
## @if ($loop->last){{ __(':person started the conversation', ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@else{{ __(':person replied', ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@endif, {{ __('on :date', ['date' => \App\Models\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@endif:
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
