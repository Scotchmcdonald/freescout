{{-- Helper to check if user is subscribed to an event/medium combination --}}
@php
    $isSubscribed = false;
    if (isset($subscriptions)) {
        foreach ($subscriptions as $subscription) {
            if ($subscription->medium == $medium && $subscription->event == $event) {
                $isSubscribed = true;
                break;
            }
        }
    }
@endphp
@if($isSubscribed)checked="checked"@endif
