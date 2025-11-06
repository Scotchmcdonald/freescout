<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Conversations') }} - {{ $mailbox->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($conversations->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p>No conversations found in {{ $mailbox->name }}.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($conversations as $conversation)
                                <div class="border-b pb-4 last:border-b-0">
                                    <a href="{{ route('conversations.show', $conversation) }}" class="block hover:bg-gray-50 p-2 rounded">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-lg">{{ $conversation->subject }}</h3>
                                                <p class="text-sm text-gray-600">
                                                    {{ $conversation->customer->first_name }} {{ $conversation->customer->last_name }}
                                                    &lt;{{ $conversation->customer_email }}&gt;
                                                </p>
                                                <p class="text-sm text-gray-500 mt-1">{{ $conversation->preview }}</p>
                                            </div>
                                            <div class="text-right text-sm text-gray-500">
                                                <p>{{ $conversation->mailbox->name }}</p>
                                                <p>{{ $conversation->last_reply_at?->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $conversations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
