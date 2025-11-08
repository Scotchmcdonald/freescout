<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        /** @var view-string $viewName */
        $viewName = 'layouts.guest';
        return view($viewName);
    }
}
