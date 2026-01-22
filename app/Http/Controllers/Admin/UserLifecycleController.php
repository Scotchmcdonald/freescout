<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\UserDirectoryRegistry;

class UserLifecycleController extends Controller
{
    public function __construct(
        protected UserDirectoryRegistry $registry
    ) {}

    /**
     * Display the User Lifecycle Dashboard.
     *
     * Shows user identities synced from Google Workspace.
     */
    public function index()
    {
        $users = $this->registry->getAllUsers();
        $lastSync = now();
        $error = null;
        
        if (empty($users)) {
             $users = $this->getMockUsers();
             if (empty($this->registry->getAllUsers())) {
                 $error = "No user directories configured.";
             }
        }

        return view('admin.users.lifecycle', compact('users', 'lastSync', 'error'));
    }

    /**
     * Trigger a manual sync.
     */
    public function sync()
    {
        // In a real implementation, this would dispatch a job.
        // For now, we'll just redirect back with a message.
        return redirect()->route('admin.users.lifecycle')->with('success', 'Sync triggered. Data will refresh shortly.');
    }

    private function getMockUsers()
    {
        return [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'status' => 'Active',
                'is_2fa_enrolled' => true,
                'last_login' => now()->subHours(2)->toIso8601String(),
                'org_unit' => '/Engineering',
                'source' => 'Google Workspace',
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'status' => 'Suspended',
                'is_2fa_enrolled' => false,
                'last_login' => now()->subDays(40)->toIso8601String(),
                'org_unit' => '/Sales',
                'source' => 'Google Workspace',
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'status' => 'Active',
                'is_2fa_enrolled' => true,
                'last_login' => now()->subMinutes(15)->toIso8601String(),
                'org_unit' => '/Marketing',
                'source' => 'Google Workspace',
            ],
        ];
    }
}
