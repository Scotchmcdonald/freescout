<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $customer->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Customer Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Customer Details</h3>
                            <a href="{{ route('customers.edit', $customer) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                        </div>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">Name</div>
                                <div class="font-medium">{{ $customer->getFullName() }}</div>
                            </div>
                            
                            @if($customer->emails && count($customer->emails))
                                <div>
                                    <div class="text-gray-500 mb-1">Email(s)</div>
                                    @foreach($customer->emails as $email)
                                        <div class="font-medium">{{ $email['email'] ?? '' }}</div>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if($customer->company)
                                <div>
                                    <div class="text-gray-500 mb-1">Company</div>
                                    <div class="font-medium">{{ $customer->company }}</div>
                                </div>
                            @endif
                            
                            @if($customer->job_title)
                                <div>
                                    <div class="text-gray-500 mb-1">Job Title</div>
                                    <div class="font-medium">{{ $customer->job_title }}</div>
                                </div>
                            @endif
                            
                            @if($customer->phones && count($customer->phones))
                                <div>
                                    <div class="text-gray-500 mb-1">Phone(s)</div>
                                    @foreach($customer->phones as $phone)
                                        <div class="font-medium">{{ $phone['number'] ?? '' }}</div>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if($customer->address)
                                <div>
                                    <div class="text-gray-500 mb-1">Address</div>
                                    <div class="font-medium">
                                        {{ $customer->address }}<br>
                                        @if($customer->city || $customer->state || $customer->zip)
                                            {{ $customer->city }}{{ $customer->state ? ', ' . $customer->state : '' }} {{ $customer->zip }}<br>
                                        @endif
                                        @if($customer->country)
                                            {{ $customer->country }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            @if($customer->notes)
                                <div>
                                    <div class="text-gray-500 mb-1">Notes</div>
                                    <div class="font-medium text-gray-700">{{ $customer->notes }}</div>
                                </div>
                            @endif
                            
                            <div>
                                <div class="text-gray-500 mb-1">Customer Since</div>
                                <div class="font-medium">{{ $customer->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conversations -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Conversations ({{ $customer->conversations->count() }})</h3>
                        
                        @if($customer->conversations->isEmpty())
                            <div class="text-center py-12 text-gray-500">
                                <p>No conversations yet.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($customer->conversations as $conversation)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <a href="{{ route('conversations.show', $conversation) }}" 
                                                       class="text-base font-medium text-gray-900 hover:text-blue-600">
                                                        {{ $conversation->subject }}
                                                    </a>
                                                    @if($conversation->status == 1)
                                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                                    @elseif($conversation->status == 2)
                                                        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded">Closed</span>
                                                    @endif
                                                </div>
                                                
                                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                    <span>{{ $conversation->mailbox->name }}</span>
                                                    <span>•</span>
                                                    <span>{{ $conversation->folder->name }}</span>
                                                    @if($conversation->user)
                                                        <span>•</span>
                                                        <span>Assigned to {{ $conversation->user->getFullName() }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="text-right text-sm text-gray-500">
                                                <div>{{ $conversation->last_reply_at->diffForHumans() }}</div>
                                                <div class="mt-1">
                                                    <span class="px-2 py-0.5 bg-gray-200 rounded text-xs">
                                                        {{ $conversation->threads_count }} replies
                                                    </span>
                                                </div>
                                            </div>
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
