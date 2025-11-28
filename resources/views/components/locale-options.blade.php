{{-- Locale Options Component --}}
{{-- Usage: <x-locale-options :selected="$locale" /> --}}
{{-- This outputs option elements for a select, so wrap it: <select><x-locale-options :selected="$locale" /></select> --}}

@props(['selected' => config('app.locale')])

@php
    $locales = [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'ar' => 'العربية',
        'he' => 'עברית',
        'tr' => 'Türkçe',
        'vi' => 'Tiếng Việt',
        'th' => 'ไทย',
        'cs' => 'Čeština',
        'sk' => 'Slovenčina',
        'hu' => 'Magyar',
        'ro' => 'Română',
        'bg' => 'Български',
        'el' => 'Ελληνικά',
        'da' => 'Dansk',
        'sv' => 'Svenska',
        'no' => 'Norsk',
        'fi' => 'Suomi',
        'uk' => 'Українська',
    ];
@endphp

@foreach($locales as $code => $name)
    <option value="{{ $code }}" @selected($selected == $code)>
        {{ $name }}
    </option>
@endforeach
