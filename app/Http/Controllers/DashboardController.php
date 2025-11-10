<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory as ViewFactory;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index(Request $request): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get mailboxes the user has access to
        $mailboxes = $user->isAdmin()
            ? Mailbox::all()
            : $user->mailboxes;

        // Get active conversations count
        $activeConversations = Conversation::whereIn('mailbox_id', $mailboxes->pluck('id'))
            ->where('status', 1) // Active status
            ->where('state', 2) // Published state
            ->count();

        // Get unassigned conversations count
        $unassignedConversations = Conversation::whereIn('mailbox_id', $mailboxes->pluck('id'))
            ->whereNull('user_id')
            ->where('status', 1)
            ->where('state', 2)
            ->count();

        // Get stats per mailbox
        $stats = [];
        /** @var \App\Models\Mailbox $mailbox */
        foreach ($mailboxes as $mailbox) {
            $stats[$mailbox->id] = [
                'active' => Conversation::where('mailbox_id', $mailbox->id)
                    ->where('status', 1)
                    ->where('state', 2)
                    ->count(),
                'unassigned' => Conversation::where('mailbox_id', $mailbox->id)
                    ->whereNull('user_id')
                    ->where('status', 1)
                    ->where('state', 2)
                    ->count(),
            ];
        }

        return view('dashboard', compact(
            'user',
            'mailboxes',
            'activeConversations',
            'unassignedConversations',
            'stats'
        ));
    }
}
