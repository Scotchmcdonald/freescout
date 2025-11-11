{{-- Include Datepicker Component --}}
{{-- Includes flatpickr date picker library with localization support --}}

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        
        @php
            $locale = app()->getLocale();
            $localeCode = strtolower($locale);
            // Map locale codes to flatpickr locale codes
            $localeMap = [
                'zh-cn' => 'zh',
                'pt-pt' => 'pt',
                'pt-br' => 'pt',
            ];
            $flatpickrLocale = $localeMap[$localeCode] ?? $localeCode;
        @endphp
        
        {{-- Load locale if not English --}}
        @if ($locale !== 'en')
            <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/{{ $flatpickrLocale }}.js"></script>
        @endif
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Default flatpickr configuration
                flatpickr.defaultConfig = Object.assign({}, flatpickr.defaultConfig, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'M j, Y',
                    @if ($locale !== 'en')
                    locale: '{{ $flatpickrLocale }}',
                    @endif
                    // Check if 24-hour format should be used based on locale
                    @php
                        $time24hrLocales = ['de', 'fr', 'es', 'it', 'nl', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'cs', 'sk', 'hr', 'ro', 'fi', 'da', 'no', 'zh-cn'];
                        $use24hr = in_array($localeCode, $time24hrLocales);
                    @endphp
                    time_24hr: {{ $use24hr ? 'true' : 'false' }},
                });
                
                // Initialize all date picker inputs with class 'datepicker'
                flatpickr('.datepicker', {});
                
                // Initialize all datetime picker inputs with class 'datetimepicker'
                flatpickr('.datetimepicker', {
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    altFormat: 'M j, Y h:i K',
                });
                
                // Initialize all time picker inputs with class 'timepicker'
                flatpickr('.timepicker', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altFormat: 'h:i K',
                });
            });
        </script>
    @endpush
@endonce
