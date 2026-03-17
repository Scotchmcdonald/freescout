<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Integrations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />
            
            <div class="flex-1">
                @if(count($integrations) === 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-neutral-900">{{ __('No Integrations Installed') }}</h3>
                            <p class="mt-1 text-sm text-neutral-500">
                                {{ __('Install integration modules like GoogleAdmin or Action1 to configure them here.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Tab Navigation -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-t-lg border-b border-neutral-200">
                        <div class="flex -mb-px">
                            @foreach($integrations as $slug => $integration)
                                <a href="{{ route('settings.integrations', ['tab' => $slug]) }}" 
                                   class="px-6 py-3 text-sm font-medium border-b-2 {{ $activeTab === $slug ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                                    {{ $integration['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg">
                        <div class="p-6">
                            @if($activeTab === 'googleadmin' && isset($integrations['googleadmin']))
                                @include('googleadmin::settings.integrations')
                            @elseif($activeTab === 'action1' && isset($integrations['action1']))
                                @include('action1::settings.integrations')
                            @else
                                <div class="text-center py-8 text-neutral-500">
                                    {{ __('Select an integration from the tabs above.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
