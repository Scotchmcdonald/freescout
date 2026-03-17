<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
                {{ __('Event Audit Log') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.resilience.events-audit.export', request()->query()) }}" 
                   class="inline-flex items-center px-4 py-2 bg-neutral-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-neutral-700 active:bg-neutral-800 focus:outline-none focus:border-neutral-900 focus:ring ring-neutral-300 disabled:opacity-25 transition ease-in-out duration-150">
                    {{ __('Export CSV') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Filters --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('admin.resilience.events-audit') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-neutral-700 mb-1">Search Payload/Event</label>
                        <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" 
                               class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" 
                               placeholder="Keywords...">
                    </div>
                    <div>
                        <label for="event_type" class="block text-sm font-medium text-neutral-700 mb-1">Event Type</label>
                        <input type="text" name="event_type" id="event_type" value="{{ $filters['event_type'] ?? '' }}" 
                               class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" 
                               placeholder="e.g. UserCreated">
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-neutral-700 mb-1">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}" 
                               class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-neutral-700 mb-1">To Date</label>
                        <div class="flex space-x-2">
                            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}" 
                                   class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150" style="background-color: var(--theme-primary-600)">
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Events Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Channel
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Event
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider w-1/2">
                                    Payload
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($events as $event)
                                <tr class="hover:bg-neutral-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" style="color: var(--theme-text-main)">
                                        {{ $event->created_at }}
                                        <div class="text-xs text-neutral-400">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-neutral-100 text-neutral-800">
                                            {{ $event->channel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: var(--theme-primary-600)">
                                        {{ Str::limit($event->event, 40) }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono bg-neutral-50 shadow-inner">
                                        <div class="max-h-32 overflow-y-auto whitespace-pre-wrap text-neutral-700 select-all">{{ $event->payload }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-neutral-500">
                                        No events found matching your criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $events->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
