<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Ui\WidgetRegistryService;
use Tests\PureUnitTestCase;

class WidgetRegistryServiceTest extends PureUnitTestCase
{
    public function test_get_widgets_for_unknown_hook_returns_empty_collection(): void
    {
        $registry = new WidgetRegistryService;

        $widgets = $registry->getWidgetsForHook('does-not-exist');

        $this->assertCount(0, $widgets);
    }

    public function test_closure_widgets_are_sorted_by_priority_and_receive_context(): void
    {
        $registry = new WidgetRegistryService;

        $registry->register('client.show', fn (array $context): string => 'late-'.$context['id'], [], 20);
        $registry->register('client.show', fn (array $context): string => 'early-'.$context['id'], [], 5);

        $widgets = array_values($registry->getWidgetsForHook('client.show', ['id' => 42])->all());

        $this->assertSame(['early-42', 'late-42'], $widgets);
    }

    public function test_closure_widget_receives_object_context_unchanged(): void
    {
        $registry = new WidgetRegistryService;

        $registry->register('object.hook', fn (object $context): int => $context->id, [], 10);

        $widgets = $registry->getWidgetsForHook('object.hook', (object) ['id' => 7])->all();

        $this->assertSame([7], $widgets);
    }
}
