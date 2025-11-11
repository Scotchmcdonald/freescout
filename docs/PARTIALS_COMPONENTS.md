# Shared Partials Components

This document describes the reusable UI components and partials implemented for FreeScout.

## Overview

All partials are located in `resources/views/partials/` and can be included in any Blade view using `@include()`.

## Components

### 1. Flash Messages

**File**: `partials/flash_messages.blade.php`

Displays session flash messages with Tailwind styling.

**Usage**:
```blade
@include('partials.flash_messages')
```

**Supported Flash Types**:
- `flash_success` - Green success message
- `flash_success_unescaped` - Success message with HTML
- `flash_warning` - Yellow warning message
- `flash_error` - Red error message
- `flash_error_unescaped` - Error message with HTML
- Custom flashes array (supports `success`, `warning`, `danger`, `error`, `info`)

**Example**:
```php
// In controller
session()->flash('flash_success', 'Item created successfully!');
return redirect()->back();

// Custom flash array
$flashes = [
    ['type' => 'success', 'text' => 'Message text', 'unescaped' => false]
];
return view('your-view', compact('flashes'));
```

### 2. Empty State

**File**: `partials/empty.blade.php`

Displays an empty state with icon, header, text, and optional action button.

**Usage**:
```blade
@include('partials.empty', [
    'icon' => 'inbox',  // Optional: inbox, ok, envelope, search, folder, user, document, plus
    'empty_header' => 'No Items Found',
    'empty_text' => 'Get started by creating your first item.',
    'empty_action' => '<button>Create Item</button>',  // Optional
    'extra_class' => 'custom-class'  // Optional
])
```

### 3. Person Photo / Avatar

**File**: `partials/person_photo.blade.php`

Displays a person's photo or initials fallback.

**Usage**:
```blade
@include('partials.person_photo', [
    'person' => $user,  // Required: object with first_name, last_name, photo_url
    'size' => 'md'  // Optional: xs, sm, md (default), lg, xl, 2xl
])
```

**Person Object**:
The person object should have:
- `first_name` and `last_name` OR `name` OR `email`
- `photo_url` (optional) or `getPhotoUrl()` method

### 4. Sidebar Menu Toggle

**File**: `partials/sidebar_menu_toggle.blade.php`

Mobile hamburger menu toggle button with Alpine.js.

**Usage**:
```blade
<div x-data="{ sidebarOpen: false }">
    @include('partials.sidebar_menu_toggle')
    
    <div x-show="sidebarOpen">
        <!-- Sidebar content -->
    </div>
</div>
```

**Requirements**: Alpine.js must be loaded in your layout.

### 5. Locale Options

**File**: `partials/locale_options.blade.php`

Generates `<option>` tags for language selection.

**Usage**:
```blade
<select name="locale">
    @include('partials.locale_options', ['selected' => app()->getLocale()])
</select>
```

**Supported Locales** (28 languages):
English, Arabic, Chinese (Simplified), Croatian, Czech, Danish, Dutch, Finnish, French, German, Hebrew, Hungarian, Italian, Japanese, Kazakh, Korean, Norwegian, Persian, Polish, Portuguese, Brazilian Portuguese, Romanian, Russian, Spanish, Slovak, Swedish, Turkish, Ukrainian

**Configuration**:
Locales are defined in `config/app.php`:
```php
'locales' => ['en', 'fr', 'de', 'es', ...],
```

### 6. Timezone Options

**File**: `partials/timezone_options.blade.php`

Generates `<option>` tags for timezone selection.

**Usage**:
```blade
<select name="timezone">
    @include('partials.timezone_options', ['current_timezone' => 'America/New_York'])
</select>
```

**Supported Timezones**: 60+ timezones covering all major regions worldwide.

### 7. Date Picker

**Files**: 
- `partials/calendar.blade.php` (legacy wrapper)
- `partials/include_datepicker.blade.php` (main implementation)

Includes Flatpickr date picker with localization support.

**Usage**:
```blade
@include('partials.include_datepicker')

<!-- Date picker input -->
<input type="text" class="datepicker" name="due_date">

<!-- Datetime picker input -->
<input type="text" class="datetimepicker" name="scheduled_at">

<!-- Time picker input -->
<input type="text" class="timepicker" name="start_time">
```

**Features**:
- Automatic locale detection and loading
- 24-hour format for appropriate locales
- Three picker types: date, datetime, time
- Flatpickr CDN integration

**Configuration**:
The component automatically configures based on app locale and uses appropriate date/time formats.

### 8. Rich Text Editor

**File**: `partials/editor.blade.php`

Tiptap 2.x WYSIWYG editor with toolbar and advanced features.

**Usage**:
```blade
@include('partials.editor', [
    'name' => 'content',  // Required: form field name
    'value' => $content,  // Optional: initial content
    'id' => 'my-editor',  // Optional: custom ID
    'placeholder' => 'Write your message...',  // Optional
    'height' => '400px',  // Optional: editor height
    'class' => 'custom-class',  // Optional
    'showToolbar' => true,  // Optional: show/hide toolbar
    'enableMentions' => true,  // Optional: enable @mentions
    'enableVariables' => true,  // Optional: enable variables
    'enableAttachments' => false,  // Optional: enable file uploads
])
```

**Features**:
- Full WYSIWYG editing with toolbar
- Text formatting: bold, italic, underline, strikethrough
- Lists: bullet and numbered lists
- Headings: H2, H3
- Links: insert and edit hyperlinks
- Blockquotes and inline code
- Clear formatting button
- Alpine.js integration
- Saves content to hidden textarea for form submission

**Toolbar Buttons**:
- **B** - Bold (Ctrl+B)
- **I** - Italic (Ctrl+I)
- **U** - Underline (Ctrl+U)
- **S** - Strikethrough
- Bullet list
- Numbered list
- H2, H3 - Headings
- Link - Insert/edit link
- Quote - Blockquote
- Code - Inline code
- Clear - Remove formatting

**Requirements**: 
- Alpine.js must be loaded
- Tiptap loaded via CDN (automatic via @push directive)

## Styling

All components use **Tailwind CSS** for styling and are designed to integrate seamlessly with the Laravel 11 Breeze starter kit aesthetic.

### Color Schemes

Flash messages use semantic colors:
- Success: Green (`bg-green-50`, `text-green-800`)
- Warning: Yellow (`bg-yellow-50`, `text-yellow-800`)
- Error/Danger: Red (`bg-red-50`, `text-red-800`)
- Info: Blue (`bg-blue-50`, `text-blue-800`)

## Accessibility

All components include:
- ARIA labels for screen readers
- Semantic HTML elements
- Keyboard navigation support
- Focus states
- Color contrast compliance

## Browser Support

Components are tested and supported on:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies

### External Libraries

1. **Flatpickr** (Date Picker)
   - CDN: https://cdn.jsdelivr.net/npm/flatpickr
   - License: MIT

2. **Tiptap** (Rich Text Editor)
   - CDN: https://cdn.jsdelivr.net/npm/@tiptap/*
   - License: MIT

3. **Alpine.js** (Interactivity)
   - Package: `alpinejs` (via npm)
   - License: MIT

### Internal Dependencies

- Tailwind CSS (via Vite)
- Laravel Blade templating
- PHP 8.2+

## Testing

Test suite: `tests/Feature/PartialsComponentsTest.php`

Run tests:
```bash
php artisan test --filter=PartialsComponentsTest
```

## Migration from Archive

These components replace the following archive partials:
- ✅ `partials/flash_messages.blade.php` - Modernized with Tailwind
- ✅ `partials/empty.blade.php` - Updated with Heroicons
- ✅ `partials/person_photo.blade.php` - Enhanced with size options
- ✅ `partials/sidebar_menu_toggle.blade.php` - Alpine.js integration
- ✅ `partials/locale_options.blade.php` - Compatible with archive
- ✅ `partials/timezone_options.blade.php` - Identical to archive
- ✅ `partials/calendar.blade.php` - Legacy wrapper maintained
- ✅ `partials/include_datepicker.blade.php` - Modernized with CDN
- ✅ `partials/editor.blade.php` - Upgraded from Summernote to Tiptap

## Troubleshooting

### Editor not loading
- Ensure Alpine.js is loaded before the editor component
- Check browser console for JavaScript errors
- Verify Tiptap CDN is accessible

### Date picker not working
- Ensure Flatpickr CDN is accessible
- Check that inputs have correct class names (`datepicker`, `datetimepicker`, `timepicker`)
- Verify locale files are loading for non-English locales

### Flash messages not appearing
- Check that session flash is set before redirect
- Ensure the partial is included in your layout or view
- Verify Tailwind CSS is compiled and loaded

### Person photo not showing initials
- Ensure person object has `first_name` and `last_name` or `name` property
- Check that proper variable is passed to the partial

## Future Enhancements

Potential improvements for future versions:
- [ ] Mention support in rich text editor (@user)
- [ ] Variable insertion in editor ({{variable}})
- [ ] Draft autosave functionality
- [ ] Image upload in editor
- [ ] Emoji picker support
- [ ] Markdown mode toggle
- [ ] Custom color picker for avatars
- [ ] Gravatar integration for person photos

## Contributing

When updating these components:
1. Maintain backward compatibility with existing usage
2. Follow Tailwind CSS naming conventions
3. Ensure accessibility standards are met
4. Update tests in `PartialsComponentsTest.php`
5. Update this documentation

## License

These components are part of FreeScout and are licensed under AGPL-3.0-or-later.
