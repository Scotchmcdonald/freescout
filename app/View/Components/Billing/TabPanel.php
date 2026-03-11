<?php

namespace App\View\Components\Billing;

use Illuminate\View\Component;
use Illuminate\View\View;

class TabPanel extends Component
{
    public function __construct(
        public readonly string $id,
    ) {}

    public function render(): View
    {
        return view('components.billing.tab-panel');
    }
}
