{{-- Subscription Checkbox Component --}}
{{-- Usage: <x-subscription-checkbox :subscriptions="$subscriptions" :event="'event_name'" :medium="1" /> --}}

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
