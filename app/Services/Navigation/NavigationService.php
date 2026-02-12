<?php

declare(strict_types=1);

namespace App\Services\Navigation;

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
     * @param string $label
     * @param array<int, array<string, mixed>> $children
     * @param string|null $permission
     * @param string $icon
     * @param string $category
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
        return $this->items;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getGroupedItems(): array
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $cat = $item['category'] ?? 'General';
            $category = is_string($cat) ? $cat : 'General';
            $grouped[$category][] = $item;
        }
        return $grouped;
    }
}
