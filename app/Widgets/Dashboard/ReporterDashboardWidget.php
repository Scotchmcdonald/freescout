<?php

declare(strict_types=1);

namespace App\Widgets\Dashboard;

use App\Models\User;
use Modules\WidgetRegistry\Contracts\Widget;

/**
 * ReporterDashboardWidget
 *
 * Renders a read-only summary view for reporter-role users.
 * Shows aggregate business metrics across CRM, billing, contracts,
 * and software — no actions, just clean numbers for quick situational
 * awareness.
 *
 * Zone:       dashboard.main
 * Visible to: ROLE_REPORTER only
 */
class ReporterDashboardWidget implements Widget
{
    public function getId(): string
    {
        return 'dashboard.reporter_overview';
    }

    public function getTitle(): string
    {
        return 'Business Summary';
    }

    public function getZone(): string
    {
        return 'dashboard.main';
    }

    public function getPermission(): ?string
    {
        return null;
    }

    public function render(array $data): ?string
    {
        /** @var User|null $user */
        $user = $data['user'] ?? auth()->user();

        if (! $user instanceof User || ! $user->isReporter()) {
            return null;
        }

        $html = '<div class="space-y-6">';
        $html .= $this->renderSummaryGrid();
        $html .= $this->renderMonthlyFinancials();
        $html .= '</div>';

        return $html;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function renderSummaryGrid(): string
    {
        $stats = [];

        // CRM
        if (class_exists(\Modules\Crm\Models\Client::class)) {
            $stats['Active Clients'] = \Modules\Crm\Models\Client::where('status', 'active')->count();
            $stats['Total Contacts'] = \Modules\Crm\Models\Contact::count();
        }

        // Contracts
        if (class_exists(\Modules\ContractManager\Models\Contract::class)) {
            $stats['Active Contracts'] = \Modules\ContractManager\Models\Contract::where('status', 'active')->count();
        }

        // Software
        if (class_exists(\Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription::class)) {
            $stats['Active Licences'] = \Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription::where('status', 'active')->count();
        }

        // Assets
        if (class_exists(\Modules\AssetManagement\Models\Asset::class)) {
            $stats['Managed Assets'] = \Modules\AssetManagement\Models\Asset::count();
        }

        // PIB
        if (class_exists(\Modules\PIB\Models\Invoice::class)) {
            $stats['Open Invoices'] = \Modules\PIB\Models\Invoice::whereIn('status', ['published', 'sent', 'overdue'])->count();
        }

        if (empty($stats)) {
            return '';
        }

        $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Business Snapshot</h3>';
        $html .= '<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">';

        foreach ($stats as $label => $value) {
            $html .= '<div class="text-center p-3 bg-gray-50 rounded-lg">';
            $html .= '<p class="text-2xl font-bold text-gray-800">'.number_format((int) $value).'</p>';
            $html .= '<p class="text-xs text-gray-500 mt-1">'.e($label).'</p>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private function renderMonthlyFinancials(): string
    {
        if (! class_exists(\Modules\PIB\Models\Invoice::class)) {
            return '';
        }

        $thisMonth = now()->month;
        $thisYear = now()->year;

        $billedMtd = (float) \Modules\PIB\Models\Invoice::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');

        $collectedMtd = (float) \Modules\PIB\Models\Invoice::where('status', 'paid')
            ->whereMonth('updated_at', $thisMonth)
            ->whereYear('updated_at', $thisYear)
            ->sum('total_amount');

        $overdueTotalAr = (float) \Modules\PIB\Models\Invoice::where('status', 'overdue')
            ->sum('total_amount');

        $rows = [
            ['label' => 'Billed this month', 'value' => '$'.number_format($billedMtd, 2), 'color' => 'text-gray-800'],
            ['label' => 'Collected this month', 'value' => '$'.number_format($collectedMtd, 2), 'color' => 'text-green-700'],
            ['label' => 'Total overdue AR', 'value' => '$'.number_format($overdueTotalAr, 2), 'color' => $overdueTotalAr > 0 ? 'text-red-700 font-semibold' : 'text-green-700'],
        ];

        $html = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Monthly Financials</h3>';
        $html .= '<dl class="divide-y divide-gray-100">';

        foreach ($rows as $row) {
            $html .= '<div class="flex justify-between py-2.5">';
            $html .= '<dt class="text-sm text-gray-500">'.e($row['label']).'</dt>';
            $html .= '<dd class="text-sm '.$row['color'].'">'.e($row['value']).'</dd>';
            $html .= '</div>';
        }

        $html .= '</dl></div>';

        return $html;
    }
}
