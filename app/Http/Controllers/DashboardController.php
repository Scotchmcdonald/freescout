<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\WidgetRegistry\Services\WidgetRegistryService;

class DashboardController extends Controller
{
    public function __construct(
        protected WidgetRegistryService $widgetRegistry
    ) {}

    /**
     * Show the dashboard.
     */
    public function index(Request $request): View|ViewFactory|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isClient()) {
            return redirect()->route('portal.dashboard');
        }

        // Get mailboxes the user has access to
        $mailboxes = $user->isAdmin()
            ? Mailbox::all()
            : $user->mailboxes;

        // Filter mailboxes (Eventy hook)
        \Eventy::filter('dashboard.mailboxes', $mailboxes);

        $mailboxIds = $mailboxes->pluck('id')->filter()->toArray();
        if (empty($mailboxIds)) {
            $mailboxIds = [0]; // Prevent SQL errors with empty arrays
        }

        // Get active conversations count
        $activeConversations = Conversation::whereIn('mailbox_id', $mailboxIds)
            ->where('status', Conversation::STATUS_ACTIVE)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->count();

        // Get unassigned conversations count
        $unassignedConversations = Conversation::whereIn('mailbox_id', $mailboxIds)
            ->whereNull('user_id')
            ->where('status', Conversation::STATUS_ACTIVE)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->count();

        // Get stats per mailbox
        $stats = [];
        /** @var \App\Models\Mailbox $mailbox */
        foreach ($mailboxes as $mailbox) {
            $stats[$mailbox->id] = [
                'active' => Conversation::where('mailbox_id', $mailbox->id)
                    ->where('status', Conversation::STATUS_ACTIVE)
                    ->where('state', Conversation::STATE_PUBLISHED)
                    ->count(),
                'unassigned' => Conversation::where('mailbox_id', $mailbox->id)
                    ->whereNull('user_id')
                    ->where('status', Conversation::STATUS_ACTIVE)
                    ->where('state', Conversation::STATE_PUBLISHED)
                    ->count(),
            ];
        }

        // Render role-differentiated dashboard widgets (zone: dashboard.main)
        $widgetContext = compact('user', 'activeConversations', 'unassignedConversations', 'stats');
        $dashboardWidgetsHtml = $this->widgetRegistry->renderZone('dashboard.main', $widgetContext);

        return view('dashboard', compact(
            'user',
            'mailboxes',
            'activeConversations',
            'unassignedConversations',
            'stats',
            'dashboardWidgetsHtml',
        ));
    }
}
