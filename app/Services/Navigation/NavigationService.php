<?php

namespace App\Services\Navigation;

class NavigationService
{
    protected array $items = [];

    public function registerItem(string $label, string $route, ?string $permission = null, string $icon = ''): void
    {
        $this->items[] = [
            'type' => 'link',
            'label' => $label,
            'route' => $route,
            'permission' => $permission,
            'icon' => $icon,
        ];
    }

    public function registerDropdown(string $label, array $children, ?string $permission = null, string $icon = ''): void
    {
        $this->items[] = [
            'type' => 'dropdown',
            'label' => $label,
            'children' => $children,
            'permission' => $permission,
            'icon' => $icon,
        ];
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
