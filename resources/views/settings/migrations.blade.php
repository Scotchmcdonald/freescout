<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Migrations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />
            
            <div class="flex-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    @if(session('success'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <p class="text-sm" style="color: var(--theme-status-success-text)">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 border-l-4 p-4 bg-danger-50 border-danger-400">
                            <p class="text-sm text-danger-700">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-neutral-900">{{ __('Migration Status') }}</h3>
                        <form method="POST" action="{{ route('settings.migrate') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Run Pending Migrations') }}
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Migration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Module</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($migrations as $migration)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">{{ $migration['name'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">{{ $migration['module'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($migration['status'] === 'Ran')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-success-100 text-success-800">
                                                    {{ $migration['status'] }}
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning-100 text-warning-800">
                                                    {{ $migration['status'] }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
