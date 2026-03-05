<?php

declare(strict_types=1);

namespace App\Widgets\Dashboard;

use App\Models\Conversation;
use App\Models\User;
use Modules\WidgetRegistry\Contracts\Widget;

/**
 * AgentDashboardWidget
 *
 * Renders the technical / support-focus panel for agent (ROLE_USER) users.
 * Shows conversations assigned to the current user, AI-triaged case queue
 * (CaseManager), and cases flagged for escalation.
 *
 * Zone:       dashboard.main
 * Visible to: ROLE_USER (agents/technicians) only
 */
class AgentDashboardWidget implements Widget
{
    public function getId(): string
    {
        return 'dashboard.agent_overview';
    }

    public function getTitle(): string
    {
        return 'My Queue';
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

        if (! $user instanceof User || $user->isAdmin() || $user->isFinance() || $user->isReporter()) {
            return null;
        }

        $html  = '<div class="space-y-6">';
        $html .= $this->renderConversationKpis($user, $data);
        $html .= $this->renderCaseQueue($user);
        $html .= '</div>';

        return $html;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    private function renderConversationKpis(User $user, array $data): string
    {
        /** @var int $myActive */
        $myActive = isset($data['stats']) ? 0 : 0;

        // Work off data passed by DashboardController where possible
        $assignedToMe = Conversation::where('user_id', $user->id)
            ->where('status', Conversation::STATUS_ACTIVE)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->count();

        $unassigned = $data['unassignedConversations'] ?? 0;
        $totalActive = $data['activeConversations'] ?? 0;

        $cards = [
            ['label' => 'Assigned to Me', 'value' => (string) $assignedToMe, 'sub' => 'open conversations', 'color' => $assignedToMe > 0 ? 'blue' : 'green'],
            ['label' => 'Unassigned', 'value' => (string) $unassigned, 'sub' => 'need someone', 'color' => ((int) $unassigned) > 5 ? 'red' : (((int) $unassigned) > 0 ? 'yellow' : 'green')],
            ['label' => 'Total Active', 'value' => (string) $totalActive, 'sub' => 'team pipeline', 'color' => 'gray'],
        ];

        // CaseManager counts
        if (class_exists(\Modules\CaseManager\Models\CaseRecord::class)) {
            $myCases = \Modules\CaseManager\Models\CaseRecord::where('assigned_user_id', $user->id)
                ->whereNotIn('state', ['closed'])
                ->count();
            $needsEscalation = \Modules\CaseManager\Models\CaseRecord::where('assigned_user_id', $user->id)
                ->where('needs_escalation', true)
                ->whereNotIn('state', ['closed'])
                ->count();

            $cards[] = ['label' => 'My Cases', 'value' => (string) $myCases, 'sub' => 'open AI cases', 'color' => $myCases > 0 ? 'indigo' : 'gray'];
            if ($needsEscalation > 0) {
                $cards[] = ['label' => 'Needs Escalation', 'value' => (string) $needsEscalation, 'sub' => 'urgent cases', 'color' => 'red'];
            }
        }

        $html = '<div class="grid grid-cols-2 sm:grid-cols-' . min(count($cards), 4) . ' gap-4">';
        foreach ($cards as $card) {
            $html .= $this->renderKpiCard($card);
        }
        $html .= '</div>';

        return $html;
    }

    private function renderCaseQueue(User $user): string
    {
        if (! class_exists(\Modules\CaseManager\Models\CaseRecord::class)) {
            return '';
        }

        $cases = \Modules\CaseManager\Models\CaseRecord::with('conversation')
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('state', ['closed'])
            ->orderByRaw('needs_escalation DESC')
            ->orderByDesc('updated_at')
            ->take(8)
            ->get();

        if ($cases->isEmpty()) {
            return '<div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700 font-medium">✓ No open cases assigned to you.</div>';
        }

        $stateLabels = [
            'new'              => ['bg-blue-100 text-blue-800', 'New'],
            'awaiting_clarity' => ['bg-yellow-100 text-yellow-800', 'Awaiting Clarity'],
            'ready_for_tech'   => ['bg-indigo-100 text-indigo-800', 'Ready for Tech'],
            'in_progress'      => ['bg-purple-100 text-purple-800', 'In Progress'],
            'pending_customer' => ['bg-orange-100 text-orange-800', 'Pending Customer'],
            'resolved'         => ['bg-green-100 text-green-800', 'Resolved'],
        ];

        $html  = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">My Case Queue</h3>';
        $html .= '<div class="space-y-2">';

        foreach ($cases as $case) {
            [$stateBadge, $stateLabel] = $stateLabels[$case->state] ?? ['bg-gray-100 text-gray-700', ucfirst($case->state)];
            $subject = $case->conversation?->subject ?? "Case #{$case->id}";
            $escalation = $case->needs_escalation
                ? '<span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">⚡ Escalate</span>'
                : '';

            $html .= '<div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">';
            $html .= '<div class="min-w-0 flex-1">';
            $html .= '<p class="text-sm font-medium text-gray-800 truncate">' . e($subject) . $escalation . '</p>';
            $html .= '<p class="text-xs text-gray-400">' . ($case->updated_at?->diffForHumans() ?? '—') . '</p>';
            $html .= '</div>';
            $html .= '<span class="ml-3 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . $stateBadge . '">' . $stateLabel . '</span>';
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
            'red'    => ['bg-red-50 border-red-200', 'text-red-800', 'text-red-600'],
            'yellow' => ['bg-yellow-50 border-yellow-200', 'text-yellow-800', 'text-yellow-600'],
            'green'  => ['bg-green-50 border-green-200', 'text-green-800', 'text-green-600'],
            'blue'   => ['bg-blue-50 border-blue-200', 'text-blue-800', 'text-blue-600'],
            'indigo' => ['bg-indigo-50 border-indigo-200', 'text-indigo-800', 'text-indigo-600'],
            'gray'   => ['bg-gray-50 border-gray-200', 'text-gray-700', 'text-gray-500'],
        ];

        [$wrap, $text, $sub] = $colorMap[$card['color']] ?? $colorMap['gray'];

        $html  = '<div class="rounded-xl border p-4 ' . $wrap . '">';
        $html .= '<p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">' . e($card['label']) . '</p>';
        $html .= '<p class="text-3xl font-bold ' . $text . '">' . e($card['value']) . '</p>';
        $html .= '<p class="text-xs mt-1 ' . $sub . '">' . e($card['sub']) . '</p>';
        $html .= '</div>';

        return $html;
    }
}
