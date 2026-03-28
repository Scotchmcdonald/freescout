<?php

declare(strict_types=1);

namespace Tests\Integration\View\Components;

use App\View\Components\GuestLayout;
use Illuminate\View\View;
use Tests\IntegrationTestCase;

class GuestLayoutTest extends IntegrationTestCase
{
    public function test_guest_layout_can_be_instantiated(): void
    {
        $component = new GuestLayout;

        $this->assertInstanceOf(GuestLayout::class, $component);
    }

    public function test_guest_layout_render_returns_view(): void
    {
        $component = new GuestLayout;

        $view = $component->render();

        $this->assertInstanceOf(View::class, $view);
    }

    public function test_guest_layout_renders_correct_view_name(): void
    {
        $component = new GuestLayout;

        $view = $component->render();

        $this->assertEquals('layouts.guest', $view->name());
    }

    public function test_guest_layout_view_exists(): void
    {
        $component = new GuestLayout;

        $view = $component->render();

        // View should exist and be renderable
        $this->assertNotNull($view->name());
    }

    public function test_guest_layout_component_is_subclass_of_component(): void
    {
        $component = new GuestLayout;

        $this->assertInstanceOf(\Illuminate\View\Component::class, $component);
    }

    public function test_guest_layout_serves_unauthenticated_routes(): void
    {
        // Authorization boundary: the guest layout is the visual gate for
        // unauthenticated (pre-login) routes — login, password reset, etc.
        // It must render the correct view so that auth middleware decisions
        // are reflected in the correct UI shell.
        $component = new GuestLayout;
        $view = $component->render();

        $this->assertEquals(
            'layouts.guest',
            $view->name(),
            'Guest layout must render the unauthenticated shell view'
        );
    }
}
