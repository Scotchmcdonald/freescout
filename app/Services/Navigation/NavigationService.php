<?php

declare(strict_types=1);

namespace App\Services\Navigation;

use Illuminate\Support\Facades\Route;

class NavigationService
{
    /** @var array<int, array<string, mixed>> */
    protected array $items = [];

    public function registerItem(string $label, string $route, ?string $permission = null, string $icon = '', string $category = 'General'): void
    {
        $this->items[] = [
            'type' => 'link',
            'label' => $label,
            'route' => $route,
            'permission' => $permission,
            'icon' => $icon,
            'category' => $category,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    public function registerDropdown(string $label, array $children, ?string $permission = null, string $icon = '', string $category = 'General'): void
    {
        $this->items[] = [
            'type' => 'dropdown',
            'label' => $label,
            'children' => $children,
            'permission' => $permission,
            'icon' => $icon,
            'category' => $category,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(): array
    {
        return array_values(array_filter(
            $this->normalizeItems($this->items),
            fn (array $item): bool => $this->isItemVisible($item)
        ));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getGroupedItems(): array
    {
        $grouped = [];
        foreach ($this->getItems() as $item) {
            $cat = $item['category'] ?? 'General';
            $category = is_string($cat) ? $cat : 'General';
            $grouped[$category][] = $item;
        }

        return $grouped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return array_map(function (array $item): array {
            if (($item['type'] ?? null) !== 'dropdown') {
                return $item;
            }

            $children = $item['children'] ?? [];
            if (! is_array($children)) {
                $item['children'] = [];

                return $item;
            }

            $item['children'] = array_values(array_filter(
                $children,
                fn (mixed $child): bool => is_array($child) && $this->hasValidRoute($child['route'] ?? null)
            ));

            return $item;
        }, $items);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isItemVisible(array $item): bool
    {
        if (($item['type'] ?? null) === 'dropdown') {
            $children = $item['children'] ?? [];

            return is_array($children) && count($children) > 0;
        }

        return $this->hasValidRoute($item['route'] ?? null);
    }

    private function hasValidRoute(mixed $routeName): bool
    {
        return is_string($routeName) && $routeName !== '' && Route::has($routeName);
    }
}
