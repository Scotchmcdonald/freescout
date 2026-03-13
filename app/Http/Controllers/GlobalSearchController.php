<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Crm\Models\Client; // Assuming client model location

class GlobalSearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->string('q')->toString();
        $results = [
            'tickets' => [],
            'clients' => [],
            'users' => [],
            'articles' => [],
        ];

        if (empty($query)) {
            return view('search.global', compact('results', 'query'));
        }

        // 1. Search Tickets (Conversations)
        $results['tickets'] = Conversation::where('subject', 'like', "%{$query}%")
            ->orWhere('number', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        // 2. Search Clients (if module exists)
        if (class_exists(\Modules\Crm\Models\Client::class)) {
            $results['clients'] = \Modules\Crm\Models\Client::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(5)
                ->get();
        }

        // 3. Search Users
        $results['users'] = User::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        // 4. Search Knowledge Base (if module exists)
        if (class_exists(\Modules\KnowledgeBase\Models\Article::class)) {
            $results['articles'] = \Modules\KnowledgeBase\Models\Article::where('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%")
                ->limit(5)
                ->get();
        }

        return view('search.global', compact('results', 'query'));
    }
}
