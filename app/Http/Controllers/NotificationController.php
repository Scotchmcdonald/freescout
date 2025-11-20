<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        // Mock notifications for now
        return view('notifications.index', ['notifications' => []]);
    }

    public function markAsRead($id)
    {
        return back();
    }
}
