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
{{-- Office Hours feature placeholder - uncomment when route is implemented
<a href="{{ route('mailboxes.office_hours', $mailbox) }}" 
   class="list-group-item list-group-item-action @if(Route::currentRouteName() == 'mailboxes.office_hours') active @endif">
    {{ __('Office Hours') }}
</a>
--}}
