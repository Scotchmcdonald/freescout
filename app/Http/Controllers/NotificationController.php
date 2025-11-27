<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        // Mock notifications for now
        return view('notifications.index', ['notifications' => []]);
    }

    public function markAsRead(int|string $id): \Illuminate\Http\RedirectResponse
    {
        return back();
    }
}
