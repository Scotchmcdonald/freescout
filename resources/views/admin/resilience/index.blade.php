<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
                {{ __('Resilience Dashboard') }}
            </h2>
            <a href="{{ route('admin.resilience.events-audit') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('View Audit Log') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- Circuit Breaker Section --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">Circuit Breakers</h3>
                </div>

                {{-- Emergency Alert Zone --}}
                @if($openCircuits > 0)
                    <div class="border-l-4 p-4 rounded-lg mb-6" 
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
                    @foreach($circuitBreakers as $service)
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
                                    $badgeText = ucfirst(str_replace('_', ' ', $service['state']));
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeColor }}">
                                    {{ $badgeIcon }} {{ $badgeText }}
                                </span>
                            </div>

                            <dl class="space-y-2 text-sm" style="color: var(--theme-text-secondary)">
                                <div class="flex justify-between">
                                    <dt>Failures detected:</dt>
                                    <dd class="font-medium text-red-600">{{ $service['failure_count'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Last changed:</dt>
                                    <dd>{{ $service['last_checked_human'] }}</dd>
                                </div>
                                @if($service['state'] !== 'closed')
                                    <div class="flex justify-between">
                                        <dt>Retry enabled:</dt>
                                        <dd>{{ $service['can_retry'] ? 'Yes' : 'Wait...' }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if($service['state'] !== 'closed')
                                <div class="mt-4">
                                    <form method="POST" action="{{ route('admin.resilience.reset-circuit', $service['key']) }}">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Reset Circuit
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <hr style="border-color: var(--theme-border)">

            {{-- Rate Limiter Section --}}
            <section>
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium" style="color: var(--theme-text-main)">API Rate Limits</h3>
                </div>

                <div class="space-y-4">
                    @foreach($rateLimits as $service)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold" style="color: var(--theme-text-main)">
                                    {{ $service['name'] }}
                                </h3>
                                <span class="text-sm" style="color: var(--theme-text-muted)">
                                    Resets in: <strong>{{ $service['reset_in_human'] }}</strong>
                                </span>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium" style="color: var(--theme-text-main)">
                                        {{ number_format($service['used']) }} / {{ number_format($service['limit']) }} requests
                                    </span>
                                    <span class="text-sm font-medium" style="color: var(--theme-text-main)">
                                        {{ $service['used_percent'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all duration-300 {{ 
                                        $service['color'] === 'danger' ? 'bg-red-600' : 
                                        ($service['color'] === 'warning' ? 'bg-yellow-500' : 'bg-green-600') 
                                    }}" 
                                         style="width: {{ min(100, $service['used_percent']) }}%">
                                    </div>
                                </div>
                            </div>

                            {{-- Warning Alerts --}}
                            @if($service['used_percent'] >= 90)
                                <div class="border-l-4 p-4" 
                                     style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-text);">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5" style="color: var(--theme-status-error-text)" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm" style="color: var(--theme-status-error-text)">
                                                Critical quota usage! Consider increasing limits or reducing demand.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @elseif($service['used_percent'] >= 70)
                                <div class="border-l-4 p-4" 
                                     style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-text);">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5" style="color: var(--theme-status-warning-text)" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm" style="color: var(--theme-status-warning-text)">
                                                High usage detected. Monitor closely.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
