<?php

declare(strict_types=1);

namespace Tests\Unit\View\Components;

use App\View\Components\AppLayout;
use Illuminate\View\View;
use Tests\TestCase;

class AppLayoutTest extends TestCase
{
    public function test_app_layout_can_be_instantiated(): void
    {
        $component = new AppLayout;

        $this->assertInstanceOf(AppLayout::class, $component);
    }

    public function test_app_layout_render_returns_view(): void
    {
        $component = new AppLayout;

        $view = $component->render();

        $this->assertInstanceOf(View::class, $view);
    }

    public function test_app_layout_renders_correct_view_name(): void
    {
        $component = new AppLayout;

        $view = $component->render();

        $this->assertEquals('layouts.app', $view->name());
    }

    public function test_app_layout_view_exists(): void
    {
        $component = new AppLayout;

        $view = $component->render();

        // View should exist and be renderable
        $this->assertNotNull($view->name());
    }

    public function test_app_layout_component_is_subclass_of_component(): void
    {
        $component = new AppLayout;

        $this->assertInstanceOf(\Illuminate\View\Component::class, $component);
    }
}
