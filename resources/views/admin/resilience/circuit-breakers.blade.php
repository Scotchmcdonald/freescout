<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('Service Resilience Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Emergency Alert Zone --}}
            @if($openCircuits > 0)
                <div class="border-l-4 p-4 rounded-lg" 
                     style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-text);">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6" style="color: var(--theme-status-error-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-lg font-semibold" style="color: var(--theme-status-error-text)">
                                ⚠️ {{ $openCircuits }} Service(s) Degraded
                            </h3>
                            <p class="mt-1 text-sm" style="color: var(--theme-status-error-text)">
                                External integrations are experiencing failures. Manual intervention may be required.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Live Status Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($services as $service)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold" style="color: var(--theme-text-main)">
                                {{ $service['name'] }}
                            </h3>
                            @php
                                $badgeColor = match($service['state']) {
                                    'closed' => 'bg-green-100 text-green-800',
                                    'half_open' => 'bg-yellow-100 text-yellow-800',
                                    'open' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                                $badgeIcon = match($service['state']) {
                                    'closed' => '🟢',
                                    'half_open' => '🟡',
                                    'open' => '🔴',
                                    default => '⚪',
                                };
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $badgeColor }}">
                                {{ $badgeIcon }} {{ strtoupper($service['state']) }}
                            </span>
                        </div>

                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt style="color: var(--theme-text-muted)">Failure Count:</dt>
                                <dd class="font-medium" style="color: var(--theme-text-main)">
                                    {{ $service['failure_count'] }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt style="color: var(--theme-text-muted)">Last Check:</dt>
                                <dd class="font-medium" style="color: var(--theme-text-main)">
                                    {{ $service['last_checked_human'] }}
                                </dd>
                            </div>
                            @if($service['state'] === 'open' && $service['next_retry'])
                                <div class="flex justify-between">
                                    <dt style="color: var(--theme-text-muted)">Next Retry:</dt>
                                    <dd class="font-medium" style="color: var(--theme-text-main)">
                                        {{ $service['next_retry']->diffForHumans() }}
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        {{-- Action Buttons --}}
                        @if($service['state'] !== 'closed')
                            <div class="mt-4">
                                <form method="POST" action="{{ route('admin.resilience.reset-circuit', $service['key']) }}"
                                      onsubmit="return confirm('Are you sure you want to reset the circuit for {{ $service['name'] }}? This will immediately test the service.');">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full px-4 py-2 text-sm font-medium text-white rounded-md transition-colors"
                                            style="background-color: var(--theme-primary-600); hover:opacity-90;">
                                        Reset Circuit
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- State Transition History --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--theme-text-main)">
                        State Transition Log (Last 24 Hours)
                    </h3>
                    
                    @if(empty($transitions))
                        <p class="text-center py-8" style="color: var(--theme-text-muted)">
                            No state transitions in the last 24 hours.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Timestamp
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Service
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Old State
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            New State
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trigger
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($transitions as $transition)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-main)">
                                                {{ \Carbon\Carbon::parse($transition['timestamp'])->format('M d, H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: var(--theme-text-main)">
                                                {{ $transition['service'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-muted)">
                                                {{ $transition['old_state'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ 
                                                    $transition['new_state'] === 'closed' ? 'bg-green-100 text-green-800' : 
                                                    ($transition['new_state'] === 'half_open' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') 
                                                }}">
                                                    {{ $transition['new_state'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-muted)">
                                                {{ $transition['trigger'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
