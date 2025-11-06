<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">{{ __('User Details') }}</h3>
                            @can('update', $user)
                                <a href="{{ route('users.edit', $user) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                            @endcan
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-semibold">
                                {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name ?? '', 0, 1) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-xl font-semibold">{{ $user->getFullName() }}</div>
                                <div class="text-sm text-gray-600">{{ $user->email }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">{{ __('Role') }}</div>
                                <div class="font-medium">
                                    @if($user->role == 1)
                                        <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">Administrator</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">User</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">{{ __('Status') }}</div>
                                <div class="font-medium">
                                    @if($user->status == 1)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($user->job_title)
                                <div>
                                    <div class="text-gray-500 mb-1">{{ __('Job Title') }}</div>
                                    <div class="font-medium">{{ $user->job_title }}</div>
                                </div>
                            @endif
                            
                            @if($user->phone)
                                <div>
                                    <div class="text-gray-500 mb-1">{{ __('Phone') }}</div>
                                    <div class="font-medium">{{ $user->phone }}</div>
                                </div>
                            @endif
                            
                            @if($user->timezone)
                                <div>
                                    <div class="text-gray-500 mb-1">{{ __('Timezone') }}</div>
                                    <div class="font-medium">{{ $user->timezone }}</div>
                                </div>
                            @endif
                            
                            <div>
                                <div class="text-gray-500 mb-1">{{ __('Member Since') }}</div>
                                <div class="font-medium">{{ $user->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mailboxes -->
                    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Mailboxes') }} ({{ $user->mailboxes->count() }})</h3>
                        
                        @if($user->mailboxes->isEmpty())
                            <p class="text-sm text-gray-500">No mailboxes assigned</p>
                        @else
                            <div class="space-y-2">
                                @foreach($user->mailboxes as $mailbox)
                                    <a href="{{ route('mailboxes.view', $mailbox) }}" 
                                       class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                        <div class="font-medium text-gray-900">{{ $mailbox->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $mailbox->email }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Activity -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Recent Conversations') }}</h3>
                        
                        @if($user->conversations->isEmpty())
                            <div class="text-center py-12 text-gray-500">
                                <p>No conversations assigned to this user</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($user->conversations->take(10) as $conversation)
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
                                                    <span>{{ $conversation->customer->getFullName() }}</span>
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
