<x-mail::message>
# New Reply to: {{ $conversation->subject }}

A new reply has been added to conversation #{{ $conversation->number }}.

**From:** {{ $thread->user ? $thread->user->getFullName() : 'Customer' }}  
**Mailbox:** {{ $conversation->mailbox->name }}

---

{{ Str::limit(strip_tags($thread->body), 500) }}

---

<x-mail::button :url="$url">
View Conversation
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
