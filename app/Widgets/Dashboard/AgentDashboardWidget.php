<?php

declare(strict_types=1);

namespace App\Widgets\Dashboard;

use App\Models\Conversation;
use App\Models\User;
use App\Services\FocusFollowUpService;
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

        $html = '<div class="space-y-6">';
        $html .= $this->renderConversationKpis($user, $data);
        $html .= $this->renderMyFocus($user);
        $html .= $this->renderCaseQueue($user);
        $html .= '</div>';

        return $html;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
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

        $unassignedRaw = $data['unassignedConversations'] ?? 0;
        $totalActiveRaw = $data['activeConversations'] ?? 0;
        $unassigned = is_numeric($unassignedRaw) ? (int) $unassignedRaw : 0;
        $totalActive = is_numeric($totalActiveRaw) ? (int) $totalActiveRaw : 0;

        $cards = [
            ['label' => 'Assigned to Me', 'value' => (string) $assignedToMe, 'sub' => 'open conversations', 'color' => $assignedToMe > 0 ? 'blue' : 'green'],
            ['label' => 'Unassigned', 'value' => (string) $unassigned, 'sub' => 'need someone', 'color' => $unassigned > 5 ? 'red' : ($unassigned > 0 ? 'yellow' : 'green')],
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

        $html = '<div class="grid grid-cols-2 sm:grid-cols-'.min(count($cards), 4).' gap-4">';
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
            return '<div class="bg-success-50 border border-success-200 rounded-xl p-4 text-sm text-success-700 font-medium">✓ No open cases assigned to you.</div>';
        }

        $stateLabels = [
            'new' => ['bg-info-100 text-info-800', 'New'],
            'awaiting_clarity' => ['bg-warning-100 text-warning-800', 'Awaiting Clarity'],
            'ready_for_tech' => ['bg-primary-100 text-primary-800', 'Ready for Tech'],
            'in_progress' => ['bg-primary-100 text-primary-700', 'In Progress'],
            'pending_customer' => ['bg-warning-100 text-warning-700', 'Pending Customer'],
            'resolved' => ['bg-success-100 text-success-800', 'Resolved'],
        ];

        $html = '<div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">';
        $html .= '<h3 class="text-sm font-semibold text-neutral-700 uppercase tracking-wider mb-4">My Case Queue</h3>';
        $html .= '<div class="space-y-2">';

        foreach ($cases as $case) {
            [$stateBadge, $stateLabel] = $stateLabels[$case->state] ?? ['bg-neutral-100 text-neutral-700', ucfirst($case->state)];
            $subject = $case->conversation->subject ?? "Case #{$case->id}";
            $escalation = $case->needs_escalation
                ? '<span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-700">⚡ Escalate</span>'
                : '';

            $html .= '<div class="flex items-center justify-between py-2 border-b border-neutral-50 last:border-0">';
            $html .= '<div class="min-w-0 flex-1">';
            $html .= '<p class="text-sm font-medium text-neutral-800 truncate">'.e($subject).$escalation.'</p>';
            $html .= '<p class="text-xs text-neutral-400">'.($case->updated_at?->diffForHumans() ?? '—').'</p>';
            $html .= '</div>';
            $html .= '<span class="ml-3 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.$stateBadge.'">'.$stateLabel.'</span>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private function renderMyFocus(User $user): string
    {
        /** @var FocusFollowUpService $focusService */
        $focusService = app(FocusFollowUpService::class);
        $tickets = $focusService->getDueForUser($user)->take(10);

        $html = '<div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-5">';
        $html .= '<div class="flex items-center justify-between mb-4">';
        $html .= '<h3 class="text-sm font-semibold text-neutral-700 uppercase tracking-wider">My Focus</h3>';
        $html .= '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.($tickets->count() > 0 ? 'bg-warning-100 text-warning-800' : 'bg-success-100 text-success-800').'">'.$tickets->count().' due</span>';
        $html .= '</div>';

        if ($tickets->isEmpty()) {
            $html .= '<div class="bg-success-50 border border-success-200 rounded-lg p-3 text-sm text-success-700">No follow-ups due today.</div>';
            $html .= '</div>';

            return $html;
        }

        $html .= '<div class="space-y-2">';
        foreach ($tickets as $ticket) {
            $isOverdue = $ticket->next_follow_up?->isPast() ?? false;
            $wrap = $isOverdue ? 'border-danger-200 bg-danger-50' : 'border-warning-200 bg-warning-50';

            $html .= '<div class="rounded-lg border '.$wrap.' p-3">';
            $html .= '<div class="flex items-start justify-between gap-3">';
            $html .= '<div class="min-w-0">';
            $html .= '<a href="'.route('conversations.show', $ticket).'" class="text-sm font-medium text-primary-700 hover:underline">#'.$ticket->number.' '.e((string) $ticket->subject).'</a>';
            $html .= '<p class="text-xs text-neutral-600 mt-1">TSLC: '.e((string) ($ticket->time_since_last_contact ?? 'No contact yet')).'</p>';
            $html .= '<p class="text-xs '.($isOverdue ? 'text-danger-700' : 'text-warning-700').' mt-0.5">Follow-up '.e((string) ($ticket->next_follow_up?->diffForHumans() ?? 'unscheduled')).'</p>';
            $html .= '</div>';

            $html .= '<details class="text-xs">';
            $html .= '<summary class="cursor-pointer text-primary-700 font-medium">Snooze</summary>';
            $html .= '<div class="mt-2 space-y-1">';
            $html .= $this->renderSnoozeForm((int) $ticket->id, 'add_hours', '2', '+2 Hours');
            $html .= $this->renderSnoozeForm((int) $ticket->id, 'add_days', '1', '+1 Day');
            $html .= $this->renderSnoozeForm((int) $ticket->id, 'to_next_week', '1', '+Next Week');
            $html .= '</div>';
            $html .= '</details>';

            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderSnoozeForm(int $ticketId, string $field, string $value, string $label): string
    {
        $html = '<form method="POST" action="'.route('tickets.snooze', ['conversation' => $ticketId]).'">';
        $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
        $html .= '<input type="hidden" name="_method" value="PATCH">';
        $html .= '<input type="hidden" name="'.$field.'" value="'.$value.'">';
        $html .= '<button type="submit" class="w-full text-left rounded border border-primary-200 bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100">'.$label.'</button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * @param  array{label: string, value: string, sub: string, color: string}  $card
     */
    private function renderKpiCard(array $card): string
    {
        $colorMap = [
            'red' => ['bg-danger-50 border-danger-200', 'text-danger-800', 'text-danger-600'],
            'yellow' => ['bg-warning-50 border-warning-200', 'text-warning-800', 'text-warning-600'],
            'green' => ['bg-success-50 border-success-200', 'text-success-800', 'text-success-600'],
            'blue' => ['bg-info-50 border-info-200', 'text-info-800', 'text-info-600'],
            'indigo' => ['bg-primary-50 border-primary-200', 'text-primary-800', 'text-primary-600'],
            'gray' => ['bg-neutral-50 border-neutral-200', 'text-neutral-700', 'text-neutral-500'],
        ];

        [$wrap, $text, $sub] = $colorMap[$card['color']] ?? $colorMap['gray'];

        $html = '<div class="rounded-xl border p-4 '.$wrap.'">';
        $html .= '<p class="text-xs font-medium text-neutral-500 uppercase tracking-wider mb-1">'.e($card['label']).'</p>';
        $html .= '<p class="text-3xl font-bold '.$text.'">'.e($card['value']).'</p>';
        $html .= '<p class="text-xs mt-1 '.$sub.'">'.e($card['sub']).'</p>';
        $html .= '</div>';

        return $html;
    }
}
