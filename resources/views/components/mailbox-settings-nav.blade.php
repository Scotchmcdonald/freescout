{{-- Mailbox Settings Nav Component --}}
{{-- Usage: <x-mailbox-settings-nav :mailbox="$mailbox" /> --}}

@props(['mailbox'])

<a href="{{ route('mailboxes.permissions', $mailbox) }}" 
   class="list-group-item list-group-item-action @if(Route::currentRouteName() == 'mailboxes.permissions') active @endif">
    {{ __('Permissions') }}
</a>
<a href="{{ route('mailboxes.auto_reply', $mailbox) }}" 
   class="list-group-item list-group-item-action @if(Route::currentRouteName() == 'mailboxes.auto_reply') active @endif">
    {{ __('Auto Reply') }}
</a>
<a href="#" class="list-group-item list-group-item-action">
    {{ __('Office Hours') }}
</a>
