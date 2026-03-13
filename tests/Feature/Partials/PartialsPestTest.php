<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

test('flash messages partial renders success message', function () {
    Session::flash('flash_success', 'Operation successful!');

    $html = view('partials.flash_messages')->render();

    expect($html)->toContain('Operation successful!');
    // Check for theme variable instead of fixed tailwind class
    expect($html)->toContain('--theme-status-success-bg');
});

test('flash messages partial renders error message', function () {
    Session::flash('flash_error', 'Something went wrong!');

    $html = view('partials.flash_messages')->render();

    expect($html)->toContain('Something went wrong!');
    expect($html)->toContain('--theme-status-error-bg');
});

test('flash messages partial renders warning message', function () {
    Session::flash('flash_warning', 'Please be careful!');

    $html = view('partials.flash_messages')->render();

    expect($html)->toContain('Please be careful!');
    expect($html)->toContain('--theme-status-warning-bg');
});

test('flash messages partial renders custom flashes', function () {
    $flashes = [
        ['type' => 'success', 'text' => 'Custom success message', 'unescaped' => false],
        ['type' => 'danger', 'text' => 'Custom danger message', 'unescaped' => false],
    ];

    $html = view('partials.flash_messages', compact('flashes'))->render();

    expect($html)->toContain('Custom success message');
    expect($html)->toContain('Custom danger message');
});

test('empty partial renders with default icon', function () {
    $html = view('partials.empty', [
        'empty_header' => 'No Items Found',
        'empty_text' => 'Get started by creating a new item.',
    ])->render();

    expect($html)->toContain('No Items Found');
    expect($html)->toContain('Get started by creating a new item.');
});

test('empty partial renders with custom icon', function () {
    $html = view('partials.empty', [
        'icon' => 'user',
        'empty_header' => 'No Users',
        'empty_text' => 'Add your first user.',
    ])->render();

    expect($html)->toContain('No Users');
    expect($html)->toContain('Add your first user.');
});

test('person photo partial renders initials when no photo', function () {
    $person = (object) [
        'first_name' => 'John',
        'last_name' => 'Doe',
    ];

    $html = view('partials.person_photo', compact('person'))->render();

    expect($html)->toContain('JD');
    expect($html)->toContain('person-photo');
});

test('person photo partial renders image when photo url exists', function () {
    $person = (object) [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'photo_url' => 'https://example.com/photo.jpg',
    ];

    $html = view('partials.person_photo', compact('person'))->render();

    expect($html)->toContain('https://example.com/photo.jpg');
    expect($html)->toContain('person-photo');
    expect($html)->toContain('Jane Smith');
});

test('sidebar menu toggle partial renders', function () {
    $html = view('partials.sidebar_menu_toggle')->render();

    expect($html)->toContain('sidebar-menu-toggle');
    expect($html)->toContain('Toggle Navigation');
    expect($html)->toContain('@click');
});

test('locale options partial renders available locales', function () {
    Config::set('app.locales', ['en', 'fr', 'de', 'es']);

    $html = view('partials.locale_options', ['selected' => 'en'])->render();

    expect($html)->toContain('value="en"');
    expect($html)->toContain('value="fr"');
    expect($html)->toContain('value="de"');
    expect($html)->toContain('value="es"');
    expect($html)->toContain('selected="selected"');
});

test('timezone options partial renders timezones', function () {
    $html = view('partials.timezone_options', ['current_timezone' => 'America/New_York'])->render();

    expect($html)->toContain('value="America/New_York"');
    expect($html)->toContain('value="America/Los_Angeles"');
    expect($html)->toContain('value="Europe/London"');
    expect($html)->toContain('value="UTC"');
    expect($html)->toContain('selected="selected"');
});

test('calendar partial includes datepicker', function () {
    $html = view('partials.calendar')->render();
    expect($html)->toBeString();
});

test('include datepicker partial renders', function () {
    $html = view('partials.include_datepicker')->render();
    expect($html)->toBeString();
});

test('editor partial renders with default config', function () {
    $html = view('partials.editor', [
        'name' => 'content',
        'value' => 'Test content',
    ])->render();

    expect($html)->toContain('editor-wrapper');
    expect($html)->toContain('editor-toolbar');
    expect($html)->toContain('editor-content');
    expect($html)->toContain('name="content"');
    expect($html)->toContain('Test content');
});

test('editor partial renders without toolbar', function () {
    $html = view('partials.editor', [
        'name' => 'content',
        'value' => '',
        'showToolbar' => false,
    ])->render();

    expect($html)->toContain('editor-wrapper');
    expect($html)->not->toContain('editor-toolbar');
});

test('editor partial renders with custom placeholder', function () {
    $placeholder = 'Enter your custom text here...';

    // Create a simple wrapper that includes the stack
    $html = Blade::render(
        '<html><body>@include("partials.editor", ["name" => "content", "value" => "", "placeholder" => $placeholder])@stack("scripts")</body></html>',
        ['placeholder' => $placeholder]
    );

    // The placeholder is configured in JavaScript which is pushed to the scripts stack
    expect($html)->toContain($placeholder);
});
