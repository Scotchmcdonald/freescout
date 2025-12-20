<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Global Style Guide') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">
            
            <!-- Status Badges -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Status Badges</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Success</h4>
                        <div class="space-y-2">
                            <x-status-badge status="success" />
                            <x-status-badge status="completed" />
                            <x-status-badge status="synced" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Processing (Pulse)</h4>
                        <div class="space-y-2">
                            <x-status-badge status="processing" />
                            <x-status-badge status="migrating" />
                            <x-status-badge status="scanning" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Warning</h4>
                        <div class="space-y-2">
                            <x-status-badge status="warning" />
                            <x-status-badge status="pending" />
                            <x-status-badge status="paused" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Danger</h4>
                        <div class="space-y-2">
                            <x-status-badge status="danger" />
                            <x-status-badge status="failed" />
                            <x-status-badge status="error" />
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">Custom Text</h4>
                    <x-status-badge status="success" text="Custom Success Text" />
                </div>
            </section>

            <!-- Progress Bars -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Progress Bars</h3>
                <div class="space-y-6">
                    <x-progress-bar :percent="25" label="25% Progress" />
                    <x-progress-bar :percent="50" label="50% Progress (Warning)" color="warning" />
                    <x-progress-bar :percent="75" label="75% Progress (Success)" color="success" />
                    <x-progress-bar :percent="100" label="100% Progress (Danger)" color="danger" />
                    
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Alpine.js Binding</h4>
                        <div x-data="{ progress: 0 }" x-init="setInterval(() => { progress = (progress + 10) % 110 }, 1000)">
                            <x-progress-bar alpine="progress" label="Live Progress" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Smart Stepper -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Smart Stepper</h3>
                @php
                    $steps = ['Discovery', 'Mapping', 'Verification', 'Execution'];
                @endphp
                <div class="space-y-8">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 1</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="1" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 2</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="2" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 3</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="3" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Completed</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="5" />
                    </div>
                </div>
            </section>

            <!-- Troubleshooting Cards -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Troubleshooting Cards</h3>
                <div class="space-y-4">
                    <x-troubleshooting-card 
                        title="Connection Failed" 
                        body="Unable to connect to the IMAP server. Please check your credentials and try again." 
                        type="error"
                        actionText="Retry Connection"
                    />
                    
                    <x-troubleshooting-card 
                        title="System Throttled" 
                        body="The remote server is limiting connection attempts. We have paused the migration." 
                        type="warning"
                        code="429 Too Many Requests"
                    />

                    <x-troubleshooting-card 
                        title="Migration Complete" 
                        body="All mailboxes have been successfully migrated." 
                        type="success"
                        actionText="View Report"
                    />
                </div>
            </section>

            <!-- Activity Drawer Trigger -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Activity Drawer</h3>
                <button @click="$dispatch('open-activity-drawer')" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Open Activity Drawer
                </button>
            </section>

        </div>
    </div>
</x-app-layout>