{{-- Subscription Checkbox Attribute Component --}}
{{-- 
This component outputs the "checked" attribute for a checkbox input.
Used to determine if a user is subscribed to a specific event/medium combination.

Usage within a checkbox input:
<input type="checkbox" name="subscriptions[]" value="{{ $event }}" 
    <x-subscription-checkbox :subscriptions="$subscriptions" :event="'event_name'" :medium="1" />
>

Note: This is a helper component that only outputs the checked attribute,
not a complete checkbox element. Use it within an existing input element.
--}}

@props([
    'subscriptions' => [],
    'event' => '',
    'medium' => 1,
])

@php
    $isSubscribed = false;
    foreach ($subscriptions as $subscription) {
        if ($subscription->medium == $medium && $subscription->event == $event) {
            $isSubscribed = true;
            break;
        }
    }
@endphp

@if($isSubscribed)checked="checked"@endif
