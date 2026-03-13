<?php

declare(strict_types=1);

namespace App\View\Components\Billing;

use Illuminate\View\Component;
use Illuminate\View\View;

class Tabs extends Component
{
    /**
     * @param  array<int, array{id: string, label: string, icon?: string}>  $tabs
     */
    public function __construct(
        public readonly array $tabs,
        public readonly string $active = '',
    ) {}

    public function render(): View
    {
        return view('components.billing.tabs');
    }
}
