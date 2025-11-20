<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index()
    {
        return view('webhooks.index'); // View might not exist, but controller logic is there
    }

    public function create()
    {
        return view('webhooks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
        ]);

        return redirect()->route('webhooks');
    }
}
