<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mailboxes') }}
            </h2>
            @can('create', App\Models\Mailbox::class)
                <a href="{{ route('mailboxes.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Create Mailbox') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Your Mailboxes</h3>
                        
                        @if($mailboxes->isEmpty())
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <p class="text-sm text-yellow-700">
                                    You don't have access to any mailboxes yet.
                                </p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($mailboxes as $mailbox)
                                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ $mailbox->name }}
                                            </h4>
                                            @if($mailbox->is_default)
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                    Default
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mb-4">{{ $mailbox->email }}</p>
                                        
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Active Conversations:</span>
                                                <span class="font-medium text-blue-600">
                                                    {{ $mailbox->conversations()->where('status', 1)->count() }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Unassigned:</span>
                                                <span class="font-medium text-orange-600">
                                                    {{ $mailbox->conversations()->whereNull('user_id')->where('status', 1)->count() }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
                                            <a href="{{ route('mailboxes.view', $mailbox) }}" 
                                               class="flex-1 text-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                                                View
                                            </a>
                                            <a href="{{ route('conversations.create', $mailbox) }}" 
                                               class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded hover:bg-gray-300">
                                                New
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
