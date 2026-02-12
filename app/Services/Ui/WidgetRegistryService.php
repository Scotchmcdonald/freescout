<?php

declare(strict_types=1);

namespace App\Services\Ui;

use Illuminate\Support\Collection;

class WidgetRegistryService
{
    /**
     * @var array<string, list<array<string, mixed>>>
     */
    protected array $widgets = [];

    /**
     * Register a widget for a specific hook.
     *
     * @param string $hook The location identifier (e.g., 'admin.client.show')
     * @param string|\Closure $view The view name or closure that returns html/data
     * @param array<string, mixed> $data Optional data to pass to the view if it's a string
     * @param int $priority Order of display (lower = first)
     */
    public function register(string $hook, string|\Closure $view, array $data = [], int $priority = 10): void
    {
        $this->widgets[$hook][] = [
            'view' => $view,
            'data' => $data,
            'priority' => $priority,
        ];
    }

    /**
     * Get all rendered widgets for a hook.
     *
     * @param string $hook
     * @param mixed $context Context data (e.g., the Client model) to pass to closure widgets
     * @return Collection<int, mixed>
     */
    public function getWidgetsForHook(string $hook, mixed $context = null): Collection
    {
        if (!isset($this->widgets[$hook])) {
            return collect();
        }

        return collect($this->widgets[$hook])
            ->sortBy('priority')
            ->map(function ($widget) use ($context) {
                if ($widget['view'] instanceof \Closure) {
                    return value($widget['view'], $context);
                }
                
                // If it's a view string, render it
                // We merge any static data with the context if it's an array
                /** @var array<string, mixed> $data */
                $data = $widget['data'];
                if (is_array($context)) {
                    $data = array_merge($data, $context);
                } elseif (is_object($context)) {
                    $data['context'] = $context;
                }

                $viewName = is_string($widget['view']) ? $widget['view'] : '';
                /** @var \Illuminate\View\View $viewInstance */
                $viewInstance = view($viewName, $data);
                return $viewInstance->render();
            });
    }
}
