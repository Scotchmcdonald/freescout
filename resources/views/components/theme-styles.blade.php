{{-- Theme Styles Component --}}
{{-- Usage: <x-theme-styles /> --}}
{{-- Generates CSS custom properties for theming --}}

@php
    use App\Models\Theme;
    use Illuminate\Support\Facades\Auth;

    // Prioritize user preference, then Theme facade, then default
    $userTheme = Auth::user()?->theme;
    
    if ($userTheme) {
        $currentTheme = $userTheme;
    } elseif (class_exists('Qirolab\Theme\Theme')) {
        $currentTheme = \Qirolab\Theme\Theme::active();
    } else {
        $currentTheme = config('theme.active', 'default');
    }

    // Determine if dark mode is active
    $isDarkMode = Auth::user()?->dark_mode ?? false;

    // Map legacy theme names to new structure if necessary
    $themeMap = [
        'light-classic' => 'classic',
        'dark-classic' => 'classic',
        'light-blue' => 'synthwave',
        'dark-blue' => 'synthwave',
        'blue' => 'synthwave',
        'light-green' => 'monokai',
        'dark-green' => 'monokai',
        'green' => 'monokai',
        'light-purple' => 'purple',
        'dark-purple' => 'purple',
        'dark' => 'solarized',
    ];

    $normalizedTheme = $themeMap[$currentTheme] ?? $currentTheme;
    
    // Fetch theme from Database
    $themeModel = Theme::where('name', $normalizedTheme)->first();
    
    // Fallback to default if theme not found
    if (!$themeModel) {
        $themeModel = Theme::where('name', 'default')->first();
    }

    $mode = $isDarkMode ? 'dark' : 'light';
    
    if ($themeModel) {
        $activePalette = $themeModel->config[$mode] ?? null;
    }
    
    // Hard fallback if DB is empty or something went wrong
    if (!isset($activePalette)) {
         $activePalette = [
            'primary' => ['50' => '#f0fdf4', '100' => '#dcfce7', '500' => '#22c55e', '600' => '#16a34a', '700' => '#15803d'],
            'bg' => ['main' => '#f0fdf4', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#dcfce7'],
            'text' => ['main' => '#064e3b', 'muted' => '#374151', 'inverted' => '#ffffff'],
            'border' => '#bbf7d0',
            'status' => [
                'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
            ],
            'nav' => ['bg' => '#ffffff', 'text' => '#111827', 'border' => '#e5e7eb'],
        ];
    }
@endphp

<style>
    :root {
        --theme-primary-50: {{ $activePalette['primary']['50'] }};
        --theme-primary-100: {{ $activePalette['primary']['100'] }};
        --theme-primary-500: {{ $activePalette['primary']['500'] }};
        --theme-primary-600: {{ $activePalette['primary']['600'] }};
        --theme-primary-700: {{ $activePalette['primary']['700'] }};
        
        --theme-bg-main: {{ $activePalette['bg']['main'] }};
        --theme-bg-card: {{ $activePalette['bg']['card'] }};
        --theme-bg-input: {{ $activePalette['bg']['input'] }};
        --theme-bg-hover: {{ $activePalette['bg']['hover'] ?? $activePalette['bg']['main'] }};
        
        --theme-text-main: {{ $activePalette['text']['main'] }};
        --theme-text-muted: {{ $activePalette['text']['muted'] }};
        --theme-text-inverted: {{ $activePalette['text']['inverted'] }};
        
        --theme-border: {{ $activePalette['border'] }};

        --theme-status-success-bg: {{ $activePalette['status']['success']['bg'] }};
        --theme-status-success-text: {{ $activePalette['status']['success']['text'] }};
        --theme-status-warning-bg: {{ $activePalette['status']['warning']['bg'] }};
        --theme-status-warning-text: {{ $activePalette['status']['warning']['text'] }};
        --theme-status-info-bg: {{ $activePalette['status']['info']['bg'] }};
        --theme-status-info-text: {{ $activePalette['status']['info']['text'] }};
        --theme-status-error-bg: {{ $activePalette['status']['error']['bg'] ?? ($mode === 'dark' ? '#450a0a' : '#fee2e2') }};
        --theme-status-error-text: {{ $activePalette['status']['error']['text'] ?? ($mode === 'dark' ? '#fca5a5' : '#991b1b') }};

        --theme-nav-bg: {{ $activePalette['nav']['bg'] ?? $activePalette['bg']['card'] }};
        --theme-nav-text: {{ $activePalette['nav']['text'] ?? $activePalette['text']['main'] }};
        --theme-nav-border: {{ $activePalette['nav']['border'] ?? $activePalette['border'] }};
    }

    /* Override Tailwind Utilities */
    
    /* Navigation Bar */
    .theme-nav {
        background-color: var(--theme-nav-bg) !important;
        border-color: var(--theme-nav-border) !important;
        color: var(--theme-nav-text) !important;
    }
    
    .theme-nav .text-gray-500, .theme-nav .text-gray-400 {
        color: var(--theme-nav-text) !important;
        opacity: 0.8;
    }

    /* Status Colors Overrides - Apply globally based on theme variables */
    .bg-green-100, .bg-green-50 { background-color: var(--theme-status-success-bg) !important; }
    .text-green-800, .text-green-600, .text-green-700 { color: var(--theme-status-success-text) !important; }

    .bg-yellow-100, .bg-yellow-50, .bg-orange-50 { background-color: var(--theme-status-warning-bg) !important; }
    .text-yellow-800, .text-yellow-700, .text-yellow-600, .text-orange-600, .text-orange-700 { color: var(--theme-status-warning-text) !important; }

    .bg-red-100, .bg-red-50 { background-color: var(--theme-status-error-bg) !important; }
    .text-red-800, .text-red-700, .text-red-600 { color: var(--theme-status-error-text) !important; }

    /* Backgrounds */
    .bg-blue-50 { background-color: var(--theme-primary-50) !important; }
    .bg-blue-100 { background-color: var(--theme-primary-100) !important; }
    .bg-blue-500 { background-color: var(--theme-primary-500) !important; }
    .bg-blue-600 { background-color: var(--theme-primary-600) !important; }
    .bg-blue-700 { background-color: var(--theme-primary-700) !important; }
    
    /* Hover Overrides */
    .hover\:bg-gray-50:hover { background-color: var(--theme-bg-hover) !important; }
    .hover\:bg-gray-100:hover { background-color: var(--theme-bg-hover) !important; }
    .focus\:bg-gray-100:focus { background-color: var(--theme-bg-hover) !important; }

    /* App.css Component Overrides (Fix for @apply not picking up overrides) */
    .sidebar-menu > li.active > a {
        background-color: var(--theme-primary-100) !important;
        color: var(--theme-primary-700) !important;
    }
    
    .sidebar-menu > li > a:hover {
        background-color: var(--theme-bg-hover) !important;
    }

    .editor-btn.active {
        background-color: var(--theme-primary-100) !important;
        color: var(--theme-primary-700) !important;
    }
    
    .editor-btn:hover {
        background-color: var(--theme-bg-hover) !important;
    }

    .dropdown-menu a:hover {
        background-color: var(--theme-bg-hover) !important;
    }

    /* Status Badges */
    .status-badge-active {
        background-color: var(--theme-status-success-bg) !important;
        color: var(--theme-status-success-text) !important;
    }
    
    .status-badge-pending {
        background-color: var(--theme-status-warning-bg) !important;
        color: var(--theme-status-warning-text) !important;
    }
    
    .status-badge-closed {
        background-color: var(--theme-bg-hover) !important; /* Use hover/gray for closed */
        color: var(--theme-text-muted) !important;
    }
    
    .status-badge-spam {
        background-color: #fee2e2 !important; /* Red-100 */
        color: #991b1b !important; /* Red-800 */
    }

    /* Additional App.css Overrides */
    .thread-avatar {
        background-color: var(--theme-primary-500) !important;
        color: var(--theme-text-inverted) !important;
    }

    .ProseMirror a {
        color: var(--theme-primary-600) !important;
    }

    .spinner {
        border-top-color: var(--theme-primary-600) !important;
    }

    .editor-content:focus {
        --tw-ring-color: var(--theme-primary-500) !important;
    }

    /* Text */
    .text-blue-500 { color: var(--theme-primary-500) !important; }
    .text-blue-600 { color: var(--theme-primary-600) !important; }
    .text-blue-700, .text-blue-800 { color: var(--theme-primary-700) !important; }
    
    /* Borders */
    .border-blue-500 { border-color: var(--theme-primary-500) !important; }
    .ring-blue-500 { --tw-ring-color: var(--theme-primary-500) !important; }
    .focus\:border-blue-500:focus { border-color: var(--theme-primary-500) !important; }
    .focus\:ring-blue-500:focus { --tw-ring-color: var(--theme-primary-500) !important; }

    /* Indigo Overrides (Navigation) */
    .border-indigo-400 { border-color: var(--theme-primary-600) !important; }
    .text-indigo-700 { color: var(--theme-primary-700) !important; }
    .bg-indigo-50 { background-color: var(--theme-primary-50) !important; }
    .focus\:border-indigo-700:focus { border-color: var(--theme-primary-700) !important; }
    .focus\:text-indigo-800:focus { color: var(--theme-primary-700) !important; }
    .focus\:bg-indigo-100:focus { background-color: var(--theme-primary-100) !important; }

    /* Global Overrides for Dark Mode / Theming */
    @if($isDarkMode || ($normalizedTheme === 'default' && $mode === 'dark') || $mode === 'dark')
        body {
            background-color: var(--theme-bg-main) !important;
            color: var(--theme-text-main) !important;
        }
        
        .bg-white { background-color: var(--theme-bg-card) !important; }
        .bg-gray-50 { background-color: var(--theme-bg-main) !important; }
        .bg-gray-100 { background-color: var(--theme-bg-main) !important; }
        .bg-gray-200 { background-color: var(--theme-bg-input) !important; }
        
        .text-gray-900 { color: var(--theme-text-main) !important; }
        .text-gray-800 { color: var(--theme-text-main) !important; }
        .text-gray-700 { color: var(--theme-text-main) !important; }
        .text-gray-600 { color: var(--theme-text-muted) !important; }
        .text-gray-500 { color: var(--theme-text-muted) !important; }
        
        .border-gray-200 { border-color: var(--theme-border) !important; }
        .border-gray-300 { border-color: var(--theme-border) !important; }
        
        input, select, textarea {
            background-color: var(--theme-bg-input) !important;
            color: var(--theme-text-main) !important;
            border-color: var(--theme-border) !important;
        }

        input::placeholder, textarea::placeholder {
            color: var(--theme-text-muted) !important;
            opacity: 0.7;
        }
        
        /* Fix for white text on primary buttons */
        .text-white { color: var(--theme-text-inverted) !important; }
        .bg-blue-600 .text-white { color: #ffffff !important; } /* Keep buttons white text usually */
        
        /* Primary Button Override for Dark Mode */
        .bg-gray-800 { background-color: var(--theme-primary-600) !important; }
        .bg-gray-800 .text-white { color: #ffffff !important; } /* Ensure text is white on primary buttons */
        
        /* Specific Dark Mode Fixes */
        .shadow-sm, .shadow, .shadow-lg {
            box-shadow: none !important; /* Remove shadows in dark mode usually looks better or replace with glow */
            border: 1px solid var(--theme-border) !important;
        }
        
        /* Invert icons if needed, or handle svg fills */
        svg.text-gray-400 { color: var(--theme-text-muted) !important; }
        svg.text-gray-500 { color: var(--theme-text-muted) !important; }
    @endif

    /* Theme Selection Card */
    .theme-selection-card {
        border: none !important;
        border-radius: 1rem !important; /* rounded-2xl */
        overflow: hidden !important;
    }

    .theme-selection-card:hover {
        /* border-color: var(--theme-primary-500) !important; */
        background-color: var(--theme-bg-hover) !important;
    }

    .peer:checked ~ .theme-selection-card {
        background-color: var(--theme-primary-50) !important;
        /* border-color: var(--theme-primary-500) !important; */
        --tw-ring-color: var(--theme-primary-500) !important;
    }

    .peer:checked ~ .theme-selection-card .theme-selection-check {
        background-color: var(--theme-primary-500) !important;
        border-color: var(--theme-primary-500) !important;
    }
</style>
