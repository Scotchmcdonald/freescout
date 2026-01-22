<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('API Rate Limit Monitor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @foreach($services as $service)
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
                        <div class="border-l-4 p-4 mb-4" 
                             style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-text);">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5" style="color: var(--theme-status-error-text)" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm" style="color: var(--theme-status-error-text)">
                                        🚨 <strong>Critical:</strong> API quota is nearly exhausted ({{ $service['used_percent'] }}%). Requests are being throttled. Consider reducing sync frequency or wait for quota reset.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($service['used_percent'] >= 70)
                        <div class="border-l-4 p-4 mb-4" 
                             style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-text);">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5" style="color: var(--theme-status-warning-text)" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm" style="color: var(--theme-status-warning-text)">
                                        ⚠️ <strong>Warning:</strong> API quota is at {{ $service['used_percent'] }}%. Consider reducing sync frequency to avoid throttling.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Usage Statistics --}}
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="mb-1" style="color: var(--theme-text-muted)">Hourly Limit:</dt>
                            <dd class="font-medium text-2xl" style="color: var(--theme-text-main)">
                                {{ number_format($service['limit']) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="mb-1" style="color: var(--theme-text-muted)">Used (This Hour):</dt>
                            <dd class="font-medium text-2xl" style="color: var(--theme-text-main)">
                                {{ number_format($service['used']) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="mb-1" style="color: var(--theme-text-muted)">Remaining:</dt>
                            <dd class="font-medium text-2xl" style="color: var(--theme-primary-600)">
                                {{ number_format($service['remaining']) }}
                            </dd>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
