<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-neutral-800">
                    📊 Predictive Analytics
                </h2>
                <p class="text-sm text-neutral-500 mt-1">Revenue forecasting, growth trends, and business insights</p>
            </div>
            <div class="text-sm text-neutral-500">
                Last updated: {{ now()->format('M j, Y g:i A') }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Key Metrics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow duration-200" style="border-left: 4px solid var(--theme-primary-500, #6366f1)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide font-medium text-neutral-500">Monthly Recurring Revenue</p>
                            <p class="text-3xl font-bold text-neutral-900 mt-2">${{ number_format($metrics['mrr'], 0) }}</p>
                            @if($metrics['mrr_growth'] >= 0)
                                <p class="text-sm font-medium mt-1" style="color: var(--theme-success-600, #059669)">
                                    ↑ {{ number_format($metrics['mrr_growth'], 1) }}% growth
                                </p>
                            @else
                                <p class="text-sm font-medium mt-1" style="color: var(--theme-danger-600, #dc2626)">
                                    ↓ {{ number_format(abs($metrics['mrr_growth']), 1) }}% decline
                                </p>
                            @endif
                        </div>
                        <div class="p-3 rounded-full" style="background-color: var(--theme-primary-100, #e0e7ff)">
                            <svg class="h-6 w-6" style="color: var(--theme-primary-600, #4f46e5)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow duration-200" style="border-left: 4px solid var(--theme-success-500, #10b981)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide font-medium text-neutral-500">Active Clients</p>
                            <p class="text-3xl font-bold text-neutral-900 mt-2">{{ $metrics['active_clients'] }}</p>
                            <p class="text-sm text-neutral-500 mt-1">+{{ $metrics['new_clients_this_month'] }} this month</p>
                        </div>
                        <div class="p-3 rounded-full" style="background-color: var(--theme-success-100, #d1fae5)">
                            <svg class="h-6 w-6" style="color: var(--theme-success-600, #059669)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow duration-200" style="border-left: 4px solid var(--theme-info-500, #3b82f6)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide font-medium text-neutral-500">Avg Revenue/Client</p>
                            <p class="text-3xl font-bold text-neutral-900 mt-2">${{ number_format($metrics['arpc'], 0) }}</p>
                            <p class="text-sm text-neutral-500 mt-1">All-time average</p>
                        </div>
                        <div class="p-3 rounded-full" style="background-color: var(--theme-info-100, #dbeafe)">
                            <svg class="h-6 w-6" style="color: var(--theme-info-600, #3b82f6)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow duration-200" style="border-left: 4px solid var(--theme-warning-500, #f59e0b)">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide font-medium text-neutral-500">Unbilled Services</p>
                            <p class="text-3xl font-bold text-neutral-900 mt-2">${{ number_format($metrics['unbilled_value'], 0) }}</p>
                            <p class="text-sm text-neutral-500 mt-1">Approved, pending</p>
                        </div>
                        <div class="p-3 rounded-full" style="background-color: var(--theme-warning-100, #fef3c7)">
                            <svg class="h-6 w-6" style="color: var(--theme-warning-600, #d97706)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Insights Panel --}}
            @if(!empty($insights))
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 mr-2 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Key Insights
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($insights as $insight)
                            <div class="rounded-lg border-l-4 p-4" style="background-color: var(--theme-{{ $insight['type'] }}-50, #f3f4f6); border-left-color: var(--theme-{{ $insight['type'] }}-500, #6b7280)">
                                <div class="flex items-start">
                                    @if($insight['type'] === 'success')
                                        <svg class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0" style="color: var(--theme-success-600, #059669)" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @elseif($insight['type'] === 'warning')
                                        <svg class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0" style="color: var(--theme-warning-600, #d97706)" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    @elseif($insight['type'] === 'danger')
                                        <svg class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0" style="color: var(--theme-danger-600, #dc2626)" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 mt-0.5 mr-3 flex-shrink-0" style="color: var(--theme-info-600, #3b82f6)" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold" style="color: var(--theme-{{ $insight['type'] }}-800, #1f2937)">{{ $insight['title'] }}</p>
                                        <p class="text-sm mt-1" style="color: var(--theme-{{ $insight['type'] }}-700, #374151)">{{ $insight['message'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Revenue Trends & Forecast --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Revenue Trends --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Revenue Trends (12 Months)</h3>
                    
                    @if(!empty($revenueTrends))
                        <div class="space-y-3">
                            @php
                                $maxRevenue = max(array_column($revenueTrends, 'revenue'));
                            @endphp
                            @foreach(array_slice($revenueTrends, -6) as $trend)
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-neutral-700">{{ $trend['month'] }}</span>
                                        <span class="text-sm font-bold text-neutral-900">${{ number_format($trend['revenue'], 0) }}</span>
                                    </div>
                                    <div class="w-full bg-neutral-100 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500" 
                                             style="width: {{ $maxRevenue > 0 ? ($trend['revenue'] / $maxRevenue * 100) : 0 }}%; background-color: var(--theme-primary-600, #6366f1)"></div>
                                    </div>
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $trend['invoice_count'] }} invoices</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p class="mt-2 text-sm text-neutral-500">No revenue data available</p>
                        </div>
                    @endif
                </div>

                {{-- Revenue Forecast --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Revenue Forecast (6 Months)</h3>
                    
                    @if(!empty($forecasts))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Month</th>
                                        <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Forecast</th>
                                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">Confidence</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($forecasts as $forecast)
                                        <tr class="hover:bg-neutral-50 transition-colors duration-150">
                                            <td class="px-3 py-3 whitespace-nowrap text-sm text-neutral-900">{{ $forecast['month'] }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap text-sm font-bold text-right" style="color: var(--theme-primary-600, #6366f1)">
                                                ${{ number_format($forecast['forecast'], 0) }}
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      style="background-color: var(--theme-{{ $forecast['confidence'] === 'high' ? 'success' : 'warning' }}-100, #dbeafe); color: var(--theme-{{ $forecast['confidence'] === 'high' ? 'success' : 'warning' }}-800, #1e40af)">
                                                    {{ ucfirst($forecast['confidence']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-neutral-500 mt-4 italic">
                            * Forecasts based on linear regression of recent revenue trends. Actual results may vary.
                        </p>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <p class="mt-2 text-sm text-neutral-500">Insufficient data for forecasting</p>
                            <p class="text-xs text-neutral-400 mt-1">Need at least 3 months of revenue history</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
