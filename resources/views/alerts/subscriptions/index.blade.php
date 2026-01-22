<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Alert Subscription Center') }}
            </h2>
            <div class="text-sm text-gray-500">
                {{ __('Manage your notification preferences') }}
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="alertSubscriptions()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Introduction / Empty State Context -->
                    <div class="mb-8 bg-primary-50 border-l-4 border-primary-500 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-primary-800">
                                    {{ __('Proactive Monitoring') }}
                                </h3>
                                <div class="mt-2 text-sm text-primary-700">
                                    <p>{{ __('Configure how and when you want to be notified about critical system events. Alerts are grouped by category and can be routed to different channels based on urgency.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($definitions) > 0)
                    <form @submit.prevent="saveSubscriptions" class="space-y-8">
                        
                        <!-- Alert Matrix -->
                        <div class="overflow-x-auto ring-1 ring-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                            {{ __('Alert Type') }}
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                            {{ __('Channels') }}
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                            {{ __('Frequency') }}
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                            {{ __('Status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($definitions as $key => $def)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <!-- Alert Info -->
                                            <td class="py-4 pl-4 pr-3 text-sm sm:pl-6">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-{{ $def['color'] }}-100 text-{{ $def['color'] }}-600">
                                                        <!-- Icons based on definition -->
                                                        @if($def['icon'] === 'currency-dollar')
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        @elseif($def['icon'] === 'lightning-bolt')
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        @elseif($def['icon'] === 'exclamation')
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                        @elseif($def['icon'] === 'cube')
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                                        @elseif($def['icon'] === 'chart-bar')
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                        @endif
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="font-medium text-gray-900">{{ $def['label'] }}</div>
                                                        <div class="text-gray-500">{{ $def['description'] }}</div>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mt-1 capitalize">
                                                            {{ $def['category'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Channels -->
                                            <td class="px-3 py-4 text-sm text-gray-500 text-center">
                                                <div class="flex justify-center space-x-4">
                                                    <!-- Email Toggle -->
                                                    <label class="flex flex-col items-center cursor-pointer group">
                                                        <input type="checkbox" 
                                                               x-model="subscriptions['{{ $key }}'].channels" 
                                                               value="email"
                                                               class="sr-only peer">
                                                        <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center peer-checked:bg-primary-100 peer-checked:text-primary-600 text-gray-400 transition-all duration-200 group-hover:ring-2 group-hover:ring-primary-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                        </span>
                                                        <span class="text-xs mt-1 text-gray-500 group-hover:text-primary-600">Email</span>
                                                    </label>

                                                    <!-- Slack Toggle -->
                                                    <label class="flex flex-col items-center cursor-pointer group">
                                                        <input type="checkbox" 
                                                               x-model="subscriptions['{{ $key }}'].channels" 
                                                               value="slack"
                                                               class="sr-only peer">
                                                        <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center peer-checked:bg-purple-100 peer-checked:text-purple-600 text-gray-400 transition-all duration-200 group-hover:ring-2 group-hover:ring-purple-200">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.527 2.527 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.52v-6.315zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.52v2.522h-2.52zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.522 2.521 2.527 2.527 0 0 1-2.522-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.522-2.522v-2.522h2.522zM15.165 17.688a2.527 2.527 0 0 1-2.522-2.522 2.527 2.527 0 0 1 2.522-2.522h6.312A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.522h-6.313z"/></svg>
                                                        </span>
                                                        <span class="text-xs mt-1 text-gray-500 group-hover:text-purple-600">Slack</span>
                                                    </label>

                                                    <!-- SMS Toggle -->
                                                    <label class="flex flex-col items-center cursor-pointer group">
                                                        <input type="checkbox" 
                                                               x-model="subscriptions['{{ $key }}'].channels" 
                                                               value="sms"
                                                               class="sr-only peer">
                                                        <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center peer-checked:bg-success-100 peer-checked:text-success-600 text-gray-400 transition-all duration-200 group-hover:ring-2 group-hover:ring-success-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                        </span>
                                                        <span class="text-xs mt-1 text-gray-500 group-hover:text-success-600">SMS</span>
                                                    </label>
                                                </div>
                                            </td>

                                            <!-- Frequency -->
                                            <td class="px-3 py-4 text-sm text-gray-500 text-center">
                                                <select x-model="subscriptions['{{ $key }}'].frequency" 
                                                        class="block w-full rounded-md border-gray-300 py-1.5 pl-3 pr-8 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm">
                                                    <option value="immediate">{{ __('Immediate') }}</option>
                                                    <option value="daily">{{ __('Daily Digest') }}</option>
                                                    <option value="weekly">{{ __('Weekly Summary') }}</option>
                                                </select>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-3 py-4 text-sm text-center">
                                                <button type="button" 
                                                        @click="subscriptions['{{ $key }}'].is_active = !subscriptions['{{ $key }}'].is_active"
                                                        :class="subscriptions['{{ $key }}'].is_active ? 'bg-success-100 text-success-700' : 'bg-gray-100 text-gray-400'"
                                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors duration-200">
                                                    <span x-text="subscriptions['{{ $key }}'].is_active ? '{{ __('Active') }}' : '{{ __('Paused') }}'"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button type="submit" 
                                    :disabled="saving"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="saving ? '{{ __('Saving Preferences...') }}' : '{{ __('Save Preferences') }}'"></span>
                            </button>
                        </div>
                    </form>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No Alerts Defined') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('There are currently no system alerts available for subscription.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-data="{ show: false, message: '' }"
         @notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-0 right-0 m-6 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden z-50">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900" x-text="message"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function alertSubscriptions() {
            // Initialize data from server provided definitions and existing subscriptions
            const definitions = @json($definitions);
            const saved = @json($subscriptions);
            
            let initialSubscriptions = {};
            
            // Merge definitions with saved state or defaults
            for (const [key, def] of Object.entries(definitions)) {
                if (saved[key]) {
                    initialSubscriptions[key] = {
                        channels: saved[key].channels || [],
                        frequency: saved[key].frequency || 'immediate',
                        is_active: !!saved[key].is_active,
                        alert_type: key
                    };
                } else {
                    initialSubscriptions[key] = {
                        channels: [],
                        frequency: 'immediate',
                        is_active: true,
                        alert_type: key
                    };
                }
            }

            return {
                subscriptions: initialSubscriptions,
                saving: false,

                async saveSubscriptions() {
                    this.saving = true;
                    try {
                        const response = await fetch('{{ route('alerts.subscriptions.update') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                subscriptions: this.subscriptions
                            })
                        });

                        if (response.ok) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: 'Preferences saved successfully' }));
                        } else {
                            // Handle error
                            console.error('Failed to save');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
