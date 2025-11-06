<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Welcome, {{ $user->full_name }}!</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-800">{{ $mailboxes->count() }}</div>
                            <div class="text-sm text-blue-600">Mailboxes</div>
                        </div>
                        
                        <div class="bg-green-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-800">{{ $activeConversations }}</div>
                            <div class="text-sm text-green-600">Active Conversations</div>
                        </div>
                        
                        <div class="bg-yellow-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-800">{{ $unassignedConversations }}</div>
                            <div class="text-sm text-yellow-600">Unassigned</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-semibold mb-2">Your Mailboxes</h4>
                        <ul class="space-y-2">
                            @forelse($mailboxes as $mailbox)
                                <li class="border-l-4 border-blue-500 pl-3">
                                    <a href="{{ route('mailboxes.view', $mailbox->id) }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $mailbox->name }} ({{ $mailbox->email }})
                                    </a>
                                </li>
                            @empty
                                <li class="text-gray-500">No mailboxes assigned</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
