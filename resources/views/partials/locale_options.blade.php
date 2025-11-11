{{-- Locale Options Component --}}
{{-- Generates option tags for language/locale selection --}}
{{-- Usage: <select>@include('partials.locale_options', ['selected' => $locale])</select> --}}

@php
    // Get available locales from config
    $dropdown_locales = config('app.locales', ['en']);
    
    // Allow custom locales if not disabled
    if (empty($no_custom_locales)) {
        // Check if Helper class exists and has getCustomLocales method
        if (class_exists('\App\Misc\Helper') && method_exists('\App\Misc\Helper', 'getCustomLocales')) {
            $custom_locales = \App\Misc\Helper::getCustomLocales();
            
            if (count($custom_locales)) {
                $dropdown_locales = array_unique(array_merge($dropdown_locales, $custom_locales));
            }
        }
    }
    
    // Locale name mappings
    $localeNames = [
        'en' => 'English',
        'ar' => 'العربية (Arabic)',
        'zh-CN' => '简体中文 (Chinese Simplified)',
        'hr' => 'Hrvatski (Croatian)',
        'cs' => 'Čeština (Czech)',
        'da' => 'Dansk (Danish)',
        'nl' => 'Nederlands (Dutch)',
        'fi' => 'Suomi (Finnish)',
        'fr' => 'Français (French)',
        'de' => 'Deutsch (German)',
        'he' => 'עברית (Hebrew)',
        'hu' => 'Magyar (Hungarian)',
        'it' => 'Italiano (Italian)',
        'ja' => '日本語 (Japanese)',
        'kz' => 'Қазақша (Kazakh)',
        'ko' => '한국어 (Korean)',
        'no' => 'Norsk (Norwegian)',
        'fa' => 'فارسی (Persian)',
        'pl' => 'Polski (Polish)',
        'pt-PT' => 'Português (Portuguese)',
        'pt-BR' => 'Português do Brasil (Brazilian Portuguese)',
        'ro' => 'Română (Romanian)',
        'ru' => 'Русский (Russian)',
        'es' => 'Español (Spanish)',
        'sk' => 'Slovenčina (Slovak)',
        'sv' => 'Svenska (Swedish)',
        'tr' => 'Türkçe (Turkish)',
        'uk' => 'Українська (Ukrainian)',
    ];
    
    $selected = $selected ?? app()->getLocale();
@endphp

@foreach ($dropdown_locales as $locale)
    @php
        // Get locale name from mapping or use Helper if available
        $localeName = $localeNames[$locale] ?? null;
        
        if (!$localeName && class_exists('\App\Misc\Helper') && method_exists('\App\Misc\Helper', 'getLocaleData')) {
            $data = \App\Misc\Helper::getLocaleData($locale);
            $localeName = $data['name'] ?? $locale;
        } else {
            $localeName = $localeName ?? $locale;
        }
    @endphp
    <option value="{{ $locale }}" @if ($selected == $locale)selected="selected"@endif>{{ $localeName }}</option>
@endforeach
