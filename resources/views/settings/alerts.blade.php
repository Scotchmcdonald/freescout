<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Alert Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('settings.alerts.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="space-y-6">
                            <!-- Email Alerts Section -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Email Alerts') }}</h3>
                                
                                <div class="space-y-4">
                                    <!-- System Errors -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" 
                                                   name="alerts[system_errors]" 
                                                   id="alert_system_errors"
                                                   value="1"
                                                   {{ old('alerts.system_errors', $settings['alert_system_errors'] ?? false) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3">
                                            <label for="alert_system_errors" class="font-medium text-gray-700">
                                                {{ __('System Errors') }}
                                            </label>
                                            <p class="text-sm text-gray-500">
                                                Get notified when system errors occur
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- High Email Queue -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" 
                                                   name="alerts[high_queue]" 
                                                   id="alert_high_queue"
                                                   value="1"
                                                   {{ old('alerts.high_queue', $settings['alert_high_queue'] ?? false) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label for="alert_high_queue" class="font-medium text-gray-700">
                                                {{ __('High Email Queue') }}
                                            </label>
                                            <p class="text-sm text-gray-500 mb-2">
                                                Alert when email queue exceeds threshold
                                            </p>
                                            <div class="flex items-center">
                                                <input type="number" 
                                                       name="queue_threshold" 
                                                       id="queue_threshold"
                                                       value="{{ old('queue_threshold', $settings['queue_threshold'] ?? 100) }}"
                                                       min="10"
                                                       max="10000"
                                                       class="w-32 border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-blue-500">
                                                <span class="text-sm text-gray-500 ml-2">{{ __('emails') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Failed Jobs -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" 
                                                   name="alerts[failed_jobs]" 
                                                   id="alert_failed_jobs"
                                                   value="1"
                                                   {{ old('alerts.failed_jobs', $settings['alert_failed_jobs'] ?? false) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3">
                                            <label for="alert_failed_jobs" class="font-medium text-gray-700">
                                                {{ __('Failed Jobs') }}
                                            </label>
                                            <p class="text-sm text-gray-500">
                                                Get notified when background jobs fail
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Disk Space Low -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" 
                                                   name="alerts[disk_space]" 
                                                   id="alert_disk_space"
                                                   value="1"
                                                   {{ old('alerts.disk_space', $settings['alert_disk_space'] ?? false) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3">
                                            <label for="alert_disk_space" class="font-medium text-gray-700">
                                                {{ __('Low Disk Space') }}
                                            </label>
                                            <p class="text-sm text-gray-500">
                                                Alert when disk space is running low
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Database Connection Issues -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" 
                                                   name="alerts[db_connection]" 
                                                   id="alert_db_connection"
                                                   value="1"
                                                   {{ old('alerts.db_connection', $settings['alert_db_connection'] ?? false) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3">
                                            <label for="alert_db_connection" class="font-medium text-gray-700">
                                                {{ __('Database Connection Issues') }}
                                            </label>
                                            <p class="text-sm text-gray-500">
                                                Get notified when database connectivity problems occur
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alert Recipients Section -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Alert Recipients') }}</h3>
                                
                                <div class="space-y-2">
                                    <label for="alert_recipients" class="block text-sm font-medium text-gray-700">
                                        {{ __('Email Addresses') }}
                                    </label>
                                    <textarea name="alert_recipients" 
                                              id="alert_recipients"
                                              rows="3"
                                              class="w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="admin@example.com&#10;tech@example.com">{{ old('alert_recipients', $settings['alert_recipients'] ?? '') }}</textarea>
                                    <p class="text-xs text-gray-500">{{ __('One email per line') }}</p>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                <button type="submit" 
                                        name="action" 
                                        value="test"
                                        class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('Send Test Alert') }}
                                </button>
                                
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('Save Settings') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
