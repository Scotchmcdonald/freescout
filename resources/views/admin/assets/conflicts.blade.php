<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('Conflict Resolution Console') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if($conflicts->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No conflicts found</h3>
                    <p class="mt-1 text-sm text-gray-500">All asset data is synchronized.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($conflicts as $conflict)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-400">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Asset #{{ $conflict->asset->serial_number }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        Conflict detected from source: <span class="font-medium text-gray-900">{{ $conflict->source }}</span>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $conflict->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-b border-gray-100 py-4">
                                {{-- Current State --}}
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Current State</h4>
                                    <div class="space-y-2">
                                        @foreach($conflict->proposed_changes as $key => $newValue)
                                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                                <span class="text-sm text-gray-600 font-mono">{{ $key }}</span>
                                                <span class="text-sm text-gray-900">{{ $conflict->asset->$key ?? 'null' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Proposed State --}}
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Proposed Changes</h4>
                                    <div class="space-y-2">
                                        @foreach($conflict->proposed_changes as $key => $newValue)
                                            <div class="flex justify-between items-center p-2 bg-yellow-50 rounded border border-yellow-100">
                                                <span class="text-sm text-gray-600 font-mono">{{ $key }}</span>
                                                <span class="text-sm font-semibold text-gray-900">{{ $newValue ?? 'null' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end gap-3">
                                <form action="{{ route('admin.assets.conflicts.reject', $conflict->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                        Ignore Change
                                    </button>
                                </form>

                                <form action="{{ route('admin.assets.conflicts.approve', $conflict->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150" style="background-color: var(--theme-primary-600)">
                                        Apply Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $conflicts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
