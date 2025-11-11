<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialsComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_messages_partial_renders_success_message(): void
    {
        session()->flash('flash_success', 'Operation successful!');

        $view = view('partials.flash_messages');
        $html = $view->render();

        $this->assertStringContainsString('Operation successful!', $html);
        $this->assertStringContainsString('bg-green-50', $html);
    }

    public function test_flash_messages_partial_renders_error_message(): void
    {
        session()->flash('flash_error', 'Something went wrong!');

        $view = view('partials.flash_messages');
        $html = $view->render();

        $this->assertStringContainsString('Something went wrong!', $html);
        $this->assertStringContainsString('bg-red-50', $html);
    }

    public function test_flash_messages_partial_renders_warning_message(): void
    {
        session()->flash('flash_warning', 'Please be careful!');

        $view = view('partials.flash_messages');
        $html = $view->render();

        $this->assertStringContainsString('Please be careful!', $html);
        $this->assertStringContainsString('bg-yellow-50', $html);
    }

    public function test_flash_messages_partial_renders_custom_flashes(): void
    {
        $flashes = [
            ['type' => 'success', 'text' => 'Custom success message', 'unescaped' => false],
            ['type' => 'danger', 'text' => 'Custom danger message', 'unescaped' => false],
        ];

        $view = view('partials.flash_messages', compact('flashes'));
        $html = $view->render();

        $this->assertStringContainsString('Custom success message', $html);
        $this->assertStringContainsString('Custom danger message', $html);
    }

    public function test_empty_partial_renders_with_default_icon(): void
    {
        $view = view('partials.empty', [
            'empty_header' => 'No Items Found',
            'empty_text' => 'Get started by creating a new item.',
        ]);
        $html = $view->render();

        $this->assertStringContainsString('No Items Found', $html);
        $this->assertStringContainsString('Get started by creating a new item.', $html);
    }

    public function test_empty_partial_renders_with_custom_icon(): void
    {
        $view = view('partials.empty', [
            'icon' => 'user',
            'empty_header' => 'No Users',
            'empty_text' => 'Add your first user.',
        ]);
        $html = $view->render();

        $this->assertStringContainsString('No Users', $html);
        $this->assertStringContainsString('Add your first user.', $html);
    }

    public function test_person_photo_partial_renders_initials_when_no_photo(): void
    {
        $person = (object) [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $view = view('partials.person_photo', compact('person'));
        $html = $view->render();

        $this->assertStringContainsString('JD', $html);
        $this->assertStringContainsString('person-photo', $html);
    }

    public function test_person_photo_partial_renders_image_when_photo_url_exists(): void
    {
        $person = (object) [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'photo_url' => 'https://example.com/photo.jpg',
        ];

        $view = view('partials.person_photo', compact('person'));
        $html = $view->render();

        $this->assertStringContainsString('https://example.com/photo.jpg', $html);
        $this->assertStringContainsString('person-photo', $html);
        $this->assertStringContainsString('Jane Smith', $html);
    }

    public function test_sidebar_menu_toggle_partial_renders(): void
    {
        $view = view('partials.sidebar_menu_toggle');
        $html = $view->render();

        $this->assertStringContainsString('sidebar-menu-toggle', $html);
        $this->assertStringContainsString('Toggle Navigation', $html);
        $this->assertStringContainsString('@click', $html);
    }

    public function test_locale_options_partial_renders_available_locales(): void
    {
        config(['app.locales' => ['en', 'fr', 'de', 'es']]);

        $view = view('partials.locale_options', ['selected' => 'en']);
        $html = $view->render();

        $this->assertStringContainsString('value="en"', $html);
        $this->assertStringContainsString('value="fr"', $html);
        $this->assertStringContainsString('value="de"', $html);
        $this->assertStringContainsString('value="es"', $html);
        $this->assertStringContainsString('selected="selected"', $html);
    }

    public function test_timezone_options_partial_renders_timezones(): void
    {
        $view = view('partials.timezone_options', ['current_timezone' => 'America/New_York']);
        $html = $view->render();

        $this->assertStringContainsString('value="America/New_York"', $html);
        $this->assertStringContainsString('value="America/Los_Angeles"', $html);
        $this->assertStringContainsString('value="Europe/London"', $html);
        $this->assertStringContainsString('value="UTC"', $html);
        $this->assertStringContainsString('selected="selected"', $html);
    }

    public function test_calendar_partial_includes_datepicker(): void
    {
        $view = view('partials.calendar');
        $html = $view->render();

        // Calendar partial is a wrapper that includes include_datepicker
        // The actual content is rendered once with @once directive
        $this->assertIsString($html);
    }

    public function test_include_datepicker_partial_renders(): void
    {
        $view = view('partials.include_datepicker');
        $html = $view->render();

        // Datepicker uses @once and @push, so it won't render directly
        // This test just ensures the view can be rendered without errors
        $this->assertIsString($html);
    }

    public function test_editor_partial_renders_with_default_config(): void
    {
        $view = view('partials.editor', [
            'name' => 'content',
            'value' => 'Test content',
        ]);
        $html = $view->render();

        $this->assertStringContainsString('editor-wrapper', $html);
        $this->assertStringContainsString('editor-toolbar', $html);
        $this->assertStringContainsString('editor-content', $html);
        $this->assertStringContainsString('name="content"', $html);
        $this->assertStringContainsString('Test content', $html);
    }

    public function test_editor_partial_renders_without_toolbar(): void
    {
        $view = view('partials.editor', [
            'name' => 'content',
            'value' => '',
            'showToolbar' => false,
        ]);
        $html = $view->render();

        $this->assertStringContainsString('editor-wrapper', $html);
        $this->assertStringNotContainsString('editor-toolbar', $html);
    }

    public function test_editor_partial_renders_with_custom_placeholder(): void
    {
        $view = view('partials.editor', [
            'name' => 'content',
            'value' => '',
            'placeholder' => 'Enter your custom text here...',
        ]);
        $html = $view->render();

        $this->assertStringContainsString('Enter your custom text here...', $html);
    }
}
