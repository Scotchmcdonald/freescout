<?php

declare(strict_types=1);

namespace App\Widgets\Dashboard;

use App\Models\User;
use Modules\WidgetRegistry\Contracts\Widget;

/**
 * AdminDashboardWidget
 *
 * Renders the admin operations overview panel on the main dashboard.
 * Aggregates key "needs attention" signals across Finance, Contracts, and
 * Software modules using class_exists() guards to preserve core blindness.
 *
 * Zone:       dashboard.main
 * Visible to: ROLE_ADMIN only
 */
class AdminDashboardWidget implements Widget
{
    public function getId(): string
    {
        return 'dashboard.admin_overview';
    }

    public function getTitle(): string
    {
        return 'Operations Overview';
    }

    public function getZone(): string
    {
        return 'dashboard.main';
    }

    public function getPermission(): ?string
    {
        return null; // role check performed inside render()
    }

    public function render(array $data): ?string
    {
        /** @var User|null $user */
        $user = $data['user'] ?? auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return null;
        }

        $cards = $this->buildCards();
        $sections = $this->buildSections();

        $html = '<div class="space-y-6">';

        // KPI row
        $html .= '<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">';
        foreach ($cards as $card) {
            $html .= $this->renderCard($card);
        }
        $html .= '</div>';

        // Detail sections
        foreach ($sections as $section) {
            $html .= $section;
        }

        $html .= '</div>';

        return $html;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * @return array<int, array{label: string, value: string, sub: string, color: string, href: string}>
     */
    private function buildCards(): array
    {
        $cards = [];

        // -- Overdue invoices (PIB) -----------------------------------------
        if (class_exists(\Modules\PIB\Models\Invoice::class)) {
            $overdueCount = \Modules\PIB\Models\Invoice::where('status', 'overdue')->count();
            $overdueAr = (float) \Modules\PIB\Models\Invoice::where('status', 'overdue')->sum('total_amount');
            $cards[] = [
                'label' => 'Overdue Invoices',
                'value' => (string) $overdueCount,
                'sub' => '$'.number_format($overdueAr, 0).' outstanding',
                'color' => $overdueCount > 0 ? 'red' : 'green',
                'href' => '#',
            ];
        }

        // -- Expiring contracts (ContractManager) ---------------------------
        if (class_exists(\Modules\ContractManager\Models\Contract::class)) {
            $expiringCount = \Modules\ContractManager\Models\Contract::whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>', now())
                ->where('status', 'active')
                ->count();
            $cards[] = [
                'label' => 'Contracts Expiring',
                'value' => (string) $expiringCount,
                'sub' => 'within 30 days',
                'color' => $expiringCount > 0 ? 'yellow' : 'green',
                'href' => '#',
            ];
        }

        // -- Pending quotes (ContractManager) --------------------------------
        if (class_exists(\Modules\ContractManager\Models\Quote::class)) {
            $pendingQuotes = \Modules\ContractManager\Models\Quote::whereIn('status', ['sent', 'under_review'])->count();
            $cards[] = [
                'label' => 'Pending Quotes',
                'value' => (string) $pendingQuotes,
                'sub' => 'awaiting approval',
                'color' => $pendingQuotes > 0 ? 'blue' : 'gray',
                'href' => '#',
            ];
        }

        // -- Active clients (CRM) -------------------------------------------
        if (class_exists(\Modules\Crm\Models\Client::class)) {
            $activeClients = \Modules\Crm\Models\Client::where('status', 'active')->count();
            $cards[] = [
                'label' => 'Active Clients',
                'value' => (string) $activeClients,
                'sub' => 'managed accounts',
                'color' => 'indigo',
                'href' => '#',
            ];
        }

        return $cards;
    }

    /**
     * @return list<string>
     */
    private function buildSections(): array
    {
        $sections = [];

        // -- Expiring contracts detail table ---------------------------------
        if (class_exists(\Modules\ContractManager\Models\Contract::class)) {
            $expiring = \Modules\ContractManager\Models\Contract::with('client')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(30))
                ->where('end_date', '>', now())
                ->where('status', 'active')
                ->orderBy('end_date')
                ->take(5)
                ->get();

            if ($expiring->isNotEmpty()) {
                $section = '<div class="bg-white rounded-xl shadow-sm border border-yellow-100 p-5">';
                $section .= '<div class="flex items-center justify-between mb-4">';
                $section .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">⚠ Contracts Expiring Soon</h3>';
                $section .= '</div>';
                $section .= '<div class="overflow-x-auto"><table class="min-w-full text-sm">';
                $section .= '<thead><tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100">';
                $section .= '<th class="pb-2 pr-4">Contract</th><th class="pb-2 pr-4">Client</th><th class="pb-2 pr-4">Expires</th><th class="pb-2">Days Left</th>';
                $section .= '</tr></thead><tbody class="divide-y divide-gray-50">';

                foreach ($expiring as $contract) {
                    $daysLeft = (int) now()->diffInDays($contract->end_date, false);
                    $urgency = $daysLeft <= 7 ? 'text-red-600 font-semibold' : ($daysLeft <= 14 ? 'text-orange-600' : 'text-yellow-700');
                    $clientName = $contract->client->name ?? '—';
                    $section .= '<tr class="hover:bg-gray-50">';
                    $section .= '<td class="py-2 pr-4 font-medium text-indigo-600">'.e($contract->contract_number ?? "#{$contract->id}").'</td>';
                    $section .= '<td class="py-2 pr-4 text-gray-700">'.e($clientName).'</td>';
                    $section .= '<td class="py-2 pr-4 text-gray-500">'.($contract->end_date?->format('M j, Y') ?? '—').'</td>';
                    $section .= '<td class="py-2 '.$urgency.'">'.$daysLeft.'d</td>';
                    $section .= '</tr>';
                }

                $section .= '</tbody></table></div></div>';
                $sections[] = $section;
            }
        }

        // -- Software compliance alerts ---------------------------------------
        if (class_exists(\Modules\SoftwareSubscriptions\Models\SoftwareDiscovery::class)) {
            $overDeployed = \Modules\SoftwareSubscriptions\Models\SoftwareDiscovery::where('reconciliation_status', \Modules\SoftwareSubscriptions\Models\SoftwareDiscovery::STATUS_OVER_DEPLOYED)
                ->count();

            if ($overDeployed > 0) {
                $section = '<div class="bg-white rounded-xl shadow-sm border border-red-100 p-5">';
                $section .= '<div class="flex items-center gap-2 mb-2">';
                $section .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">! Compliance</span>';
                $section .= '<p class="text-sm text-gray-700">';
                $section .= '<span class="font-semibold text-red-700">'.$overDeployed.'</span> over-deployed software instance(s) detected across client endpoints.';
                $section .= '</p></div>';
                $section .= '<a href="#" class="text-xs text-red-600 underline">Review in Software Manager →</a>';
                $section .= '</div>';
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * @param  array{label: string, value: string, sub: string, color: string, href: string}  $card
     */
    private function renderCard(array $card): string
    {
        $colorMap = [
            'red' => 'bg-red-50 border-red-200 text-red-700',
            'yellow' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
            'green' => 'bg-green-50 border-green-200 text-green-700',
            'blue' => 'bg-blue-50 border-blue-200 text-blue-700',
            'indigo' => 'bg-indigo-50 border-indigo-200 text-indigo-700',
            'gray' => 'bg-gray-50 border-gray-200 text-gray-600',
        ];
        $valueColorMap = [
            'red' => 'text-red-800',
            'yellow' => 'text-yellow-800',
            'green' => 'text-green-800',
            'blue' => 'text-blue-800',
            'indigo' => 'text-indigo-800',
            'gray' => 'text-gray-700',
        ];

        $wrapperClass = $colorMap[$card['color']] ?? $colorMap['gray'];
        $valueClass = $valueColorMap[$card['color']] ?? 'text-gray-700';

        $html = '<div class="rounded-xl border p-4 '.$wrapperClass.'">';
        $html .= '<p class="text-xs font-medium uppercase tracking-wider mb-1">'.e($card['label']).'</p>';
        $html .= '<p class="text-3xl font-bold '.$valueClass.'">'.e($card['value']).'</p>';
        $html .= '<p class="text-xs mt-1">'.e($card['sub']).'</p>';
        $html .= '</div>';

        return $html;
    }
}
