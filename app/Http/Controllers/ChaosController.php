<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChaosController extends Controller
{
    public function networkTimeout()
    {
        // Simulate a long response time to trigger client-side timeouts
        sleep(35); 
        return response()->json(['message' => 'Did not timeout?'], 200);
    }

    public function diskFull()
    {
        // Simulate HTTP 507 Insufficient Storage
        abort(507, 'Simulated Disk Full Error: Insufficient Storage');
    }
}
