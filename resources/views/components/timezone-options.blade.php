{{-- Timezone Options Component --}}
{{-- Usage: <x-timezone-options :current-timezone="$timezone" /> --}}
{{-- This outputs option elements for a select, so wrap it: <select><x-timezone-options :current-timezone="$timezone" /></select> --}}

@props(['currentTimezone' => config('app.timezone')])

@php
    $timezones = timezone_identifiers_list();
@endphp

@foreach($timezones as $timezone)
    <option value="{{ $timezone }}" @selected($currentTimezone == $timezone)>
        {{ str_replace('_', ' ', $timezone) }}
    </option>
@endforeach
