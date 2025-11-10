<?php

declare(strict_types=1);

namespace Tests\Unit\View\Components;

use App\View\Components\GuestLayout;
use Illuminate\View\View;
use Tests\TestCase;

class GuestLayoutTest extends TestCase
{
    public function test_guest_layout_can_be_instantiated(): void
    {
        $component = new GuestLayout();

        $this->assertInstanceOf(GuestLayout::class, $component);
    }

    public function test_guest_layout_render_returns_view(): void
    {
        $component = new GuestLayout();

        $view = $component->render();

        $this->assertInstanceOf(View::class, $view);
    }

    public function test_guest_layout_renders_correct_view_name(): void
    {
        $component = new GuestLayout();

        $view = $component->render();

        $this->assertEquals('layouts.guest', $view->name());
    }

    public function test_guest_layout_view_exists(): void
    {
        $component = new GuestLayout();

        $view = $component->render();

        // View should exist and be renderable
        $this->assertNotNull($view->name());
    }

    public function test_guest_layout_component_is_subclass_of_component(): void
    {
        $component = new GuestLayout();

        $this->assertInstanceOf(\Illuminate\View\Component::class, $component);
    }
}
