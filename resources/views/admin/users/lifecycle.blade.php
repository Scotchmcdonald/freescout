<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    👥 {{ __('User Lifecycle Dashboard') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Monitor user identities synced from Google Workspace and other providers</p>
            </div>
            <div class="flex items-center space-x-3">
                @if($lastSync)
                    <span class="text-sm text-gray-500">
                        Synced {{ $lastSync->diffForHumans() }}
                    </span>
                @endif
                <form action="{{ route('admin.users.lifecycle.sync') }}" method="POST" x-data="{ syncing: false }" @submit="syncing = true">
                    @csrf
                    <x-primary-button type="submit" x-bind:disabled="syncing" class="transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5 transition-transform" :class="syncing && 'animate-spin'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-show="!syncing">Sync Now</span>
                        <span x-show="syncing" x-cloak>Syncing...</span>
                    </x-primary-button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if($error)
                <x-troubleshooting-card 
                    type="warning"
                    title="Data Synchronization Issue"
                    :body="$error"
                    actionText="Check Configuration"
                />
            @endif

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <x-state-card title="Total Users" :value="count($users)" color="primary">
                    <x-slot name="icon">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </x-slot>
                </x-state-card>

                <x-state-card title="Active" :value="collect($users)->where('status', 'Active')->count()" color="success">
                    <x-slot name="icon">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-state-card>

                <x-state-card title="2FA Enrolled" :value="collect($users)->where('is_2fa_enrolled', true)->count()" color="warning">
                    <x-slot name="icon">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </x-slot>
                </x-state-card>

                <x-state-card title="Suspended" :value="collect($users)->where('status', 'Suspended')->count()" color="danger">
                     <x-slot name="icon">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </x-slot>
                </x-state-card>
            </div>

            {{-- Main Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Identity Directory
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Unified view of user accounts across Google Workspace and other providers.
                            </p>
                        </div>
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">{{ count($users) }}</span> users
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Org Unit
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Security
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Login
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Source
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-500 font-bold">
                                                    {{ substr($user['name'], 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user['name'] }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $user['email'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user['org_unit'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user['status'] === 'Active')
                                            <x-status-badge status="active" text="Active" />
                                        @else
                                            <x-status-badge status="error" :text="$user['status']" />
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user['is_2fa_enrolled'])
                                            <x-status-badge status="success" text="2FA Enabled" />
                                        @else
                                            <x-status-badge status="warning" text="No 2FA" />
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($user['last_login'])->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            @if($user['source'] === 'Google Workspace')
                                                <img class="h-4 w-4 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google">
                                            @endif
                                            {{ $user['source'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <button class="p-1.5 rounded-md transition-colors" style="color: var(--theme-primary-600, #4f46e5)" onmouseover="this.style.backgroundColor='var(--theme-primary-50, #eef2ff)'" onmouseout="this.style.backgroundColor='transparent'" title="View details" aria-label="View user details for {{ $user['name'] }}">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            @if($user['status'] === 'Suspended')
                                                <button class="p-1.5 rounded-md transition-colors" style="color: var(--theme-success-600, #10b981)" onmouseover="this.style.backgroundColor='var(--theme-success-50, #ecfdf5)'" onmouseout="this.style.backgroundColor='transparent'" title="Reactivate user" aria-label="Reactivate {{ $user['name'] }}">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500">No users found.</p>
                                        <p class="text-xs text-gray-400 mt-1">Check your Google Workspace configuration and try syncing again.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
