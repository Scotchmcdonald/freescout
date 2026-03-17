<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Notifications') }} - {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-4">
                        <x-user-sidebar-menu :user="$user" :users="$users ?? collect()" />
                    </div>
                </div>
                
                {{-- Main content --}}
                <div class="lg:col-span-3">
                    {{-- Flash messages --}}
                    <x-flash-messages />
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Notification Preferences') }}</h3>
                        
                        <form method="POST" action="{{ route('users.notifications', $user) }}">
                            @csrf
                            
                            @include('users.subscriptions_table', [
                                'subscriptions' => $subscriptions,
                                'person' => $user->id == Auth::id() ? __('me') : $user->first_name,
                                'mobile_available' => false
                            ])
                            
                            <div class="mt-6 flex items-center justify-end">
                                <button type="submit" 
                                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                                    {{ __('Save Notifications') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
