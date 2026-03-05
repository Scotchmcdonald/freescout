<?php

declare(strict_types=1);

namespace App\Widgets\Dashboard;

use App\Models\User;
use Modules\WidgetRegistry\Contracts\Widget;

/**
 * FinanceDashboardWidget
 *
 * Renders the billing & AR focus panel for finance-role users.
 * Shows outstanding receivables, overdue invoice list, and recent payment
 * activity across the PIB and Payment modules.
 *
 * Zone:       dashboard.main
 * Visible to: ROLE_FINANCE only
 */
class FinanceDashboardWidget implements Widget
{
    public function getId(): string
    {
        return 'dashboard.finance_overview';
    }

    public function getTitle(): string
    {
        return 'Billing & Receivables';
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

        if (! $user instanceof User || ! $user->isFinance()) {
            return null;
        }

        $html  = '<div class="space-y-6">';
        $html .= $this->renderArKpis();
        $html .= $this->renderOverdueTable();
        $html .= $this->renderRecentPayments();
        $html .= '</div>';

        return $html;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function renderArKpis(): string
    {
        if (! class_exists(\Modules\PIB\Models\Invoice::class)) {
            return '';
        }

        $openAr     = (float) \Modules\PIB\Models\Invoice::whereIn('status', ['published', 'sent', 'overdue'])->sum('total_amount');
        $overdueAr  = (float) \Modules\PIB\Models\Invoice::where('status', 'overdue')->sum('total_amount');
        $overdueCount = \Modules\PIB\Models\Invoice::where('status', 'overdue')->count();
        $dueThisWeek  = \Modules\PIB\Models\Invoice::whereIn('status', ['published', 'sent'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7))
            ->count();
        $collectedMtd = (float) \Modules\PIB\Models\Invoice::where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('total_amount');

        $cards = [
            ['label' => 'Total Open AR', 'value' => '$' . number_format($openAr, 0), 'sub' => 'across all clients', 'color' => $openAr > 0 ? 'yellow' : 'green'],
            ['label' => 'Overdue AR', 'value' => '$' . number_format($overdueAr, 0), 'sub' => $overdueCount . ' invoice(s)', 'color' => $overdueAr > 0 ? 'red' : 'green'],
            ['label' => 'Due This Week', 'value' => (string) $dueThisWeek, 'sub' => 'invoice(s)', 'color' => $dueThisWeek > 0 ? 'blue' : 'gray'],
            ['label' => 'Collected MTD', 'value' => '$' . number_format($collectedMtd, 0), 'sub' => 'this month', 'color' => 'green'],
        ];

        $html = '<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">';
        foreach ($cards as $card) {
            $html .= $this->renderKpiCard($card);
        }
        $html .= '</div>';

        return $html;
    }

    private function renderOverdueTable(): string
    {
        if (! class_exists(\Modules\PIB\Models\Invoice::class)) {
            return '';
        }

        $invoices = \Modules\PIB\Models\Invoice::with('client')
            ->where('status', 'overdue')
            ->orderByDesc('total_amount')
            ->take(10)
            ->get();

        if ($invoices->isEmpty()) {
            return '<div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700 font-medium">✓ No overdue invoices — all accounts current.</div>';
        }

        $html  = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Overdue Invoices</h3>';
        $html .= '<div class="overflow-x-auto"><table class="min-w-full text-sm">';
        $html .= '<thead><tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100">';
        $html .= '<th class="pb-2 pr-4">Invoice</th>';
        $html .= '<th class="pb-2 pr-4">Client</th>';
        $html .= '<th class="pb-2 pr-4 text-right">Amount</th>';
        $html .= '<th class="pb-2 pr-4">Due Date</th>';
        $html .= '<th class="pb-2">Days Overdue</th>';
        $html .= '</tr></thead><tbody class="divide-y divide-gray-50">';

        foreach ($invoices as $invoice) {
            $daysOverdue = $invoice->due_date
                ? (int) now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date))
                : '—';
            $overdueClass = is_int($daysOverdue) && $daysOverdue > 30
                ? 'text-red-700 font-semibold'
                : 'text-orange-600';
            $clientName = $invoice->client?->name ?? '—';

            $html .= '<tr class="hover:bg-gray-50">';
            $html .= '<td class="py-2 pr-4 font-medium text-indigo-600">' . e($invoice->invoice_number ?? "#{$invoice->id}") . '</td>';
            $html .= '<td class="py-2 pr-4 text-gray-700">' . e($clientName) . '</td>';
            $html .= '<td class="py-2 pr-4 text-right font-medium text-gray-900">$' . number_format($invoice->total_amount, 2) . '</td>';
            $html .= '<td class="py-2 pr-4 text-gray-500">' . ($invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M j, Y') : '—') . '</td>';
            $html .= '<td class="py-2 ' . $overdueClass . '">' . (is_int($daysOverdue) ? $daysOverdue . 'd' : $daysOverdue) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div></div>';

        return $html;
    }

    private function renderRecentPayments(): string
    {
        if (! class_exists(\Modules\Payment\Models\Payment::class)) {
            return '';
        }

        $payments = \Modules\Payment\Models\Payment::with('client')
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        if ($payments->isEmpty()) {
            return '';
        }

        $html  = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Recent Payments Received</h3>';
        $html .= '<div class="space-y-2">';

        foreach ($payments as $payment) {
            $clientName = $payment->client?->name ?? '—';
            $html .= '<div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">';
            $html .= '<div>';
            $html .= '<p class="text-sm font-medium text-gray-800">' . e($clientName) . '</p>';
            $html .= '<p class="text-xs text-gray-400">' . ($payment->created_at?->format('M j, Y g:ia') ?? '—') . '</p>';
            $html .= '</div>';
            $html .= '<span class="text-sm font-semibold text-green-700">+$' . number_format((float) $payment->amount, 2) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param array{label: string, value: string, sub: string, color: string} $card
     */
    private function renderKpiCard(array $card): string
    {
        $colorMap = [
            'red'    => 'bg-red-50 border-red-200',
            'yellow' => 'bg-yellow-50 border-yellow-200',
            'green'  => 'bg-green-50 border-green-200',
            'blue'   => 'bg-blue-50 border-blue-200',
            'gray'   => 'bg-gray-50 border-gray-200',
        ];
        $textMap = [
            'red'    => 'text-red-800',
            'yellow' => 'text-yellow-800',
            'green'  => 'text-green-800',
            'blue'   => 'text-blue-800',
            'gray'   => 'text-gray-700',
        ];
        $subMap = [
            'red'    => 'text-red-600',
            'yellow' => 'text-yellow-600',
            'green'  => 'text-green-600',
            'blue'   => 'text-blue-600',
            'gray'   => 'text-gray-500',
        ];

        $wrap  = $colorMap[$card['color']] ?? $colorMap['gray'];
        $text  = $textMap[$card['color']] ?? 'text-gray-700';
        $subCl = $subMap[$card['color']] ?? 'text-gray-500';

        $html  = '<div class="rounded-xl border p-4 ' . $wrap . '">';
        $html .= '<p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">' . e($card['label']) . '</p>';
        $html .= '<p class="text-2xl font-bold ' . $text . '">' . e($card['value']) . '</p>';
        $html .= '<p class="text-xs mt-1 ' . $subCl . '">' . e($card['sub']) . '</p>';
        $html .= '</div>';

        return $html;
    }
}
