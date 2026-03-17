<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 dark:text-neutral-200 leading-tight">
            {{ __('Search Results for: ') }} "{{ $query }}"
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">

                {{-- Tickets --}}
                @if(count($results['tickets']) > 0)
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-neutral-900 dark:text-neutral-100">
                        <h3 class="text-lg font-medium mb-4">Tickets</h3>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($results['tickets'] as $ticket)
                                <li class="py-2">
                                    <a href="{{ route('conversations.view', $ticket->id) }}" class="hover:text-primary-500">
                                        <span class="font-bold">#{{ $ticket->number }}</span> - {{ $ticket->subject }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Clients --}}
                @if(count($results['clients']) > 0)
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-neutral-900 dark:text-neutral-100">
                        <h3 class="text-lg font-medium mb-4">Clients</h3>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                             @foreach($results['clients'] as $client)
                                <li class="py-2">
                                    <a href="{{ route('clients.show', $client->id) }}" class="hover:text-primary-500">
                                        {{ $client->name }} <span class="text-neutral-500 text-sm">({{ $client->email }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Users --}}
                @if(count($results['users']) > 0)
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-neutral-900 dark:text-neutral-100">
                        <h3 class="text-lg font-medium mb-4">Users</h3>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                             @foreach($results['users'] as $user)
                                <li class="py-2">
                                    <a href="{{ route('users.show', $user->id) }}" class="hover:text-primary-500">
                                        {{ $user->first_name }} {{ $user->last_name }} <span class="text-neutral-500 text-sm">({{ $user->email }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                 {{-- Knowledge Base --}}
                 @if(count($results['articles']) > 0)
                 <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
                     <div class="p-6 text-neutral-900 dark:text-neutral-100">
                         <h3 class="text-lg font-medium mb-4">Knowledge Base</h3>
                         <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                              @foreach($results['articles'] as $article)
                                 <li class="py-2">
                                     <a href="{{ route('knowledgebase.show', $article->slug) }}" class="hover:text-primary-500">
                                         {{ $article->title }}
                                     </a>
                                 </li>
                             @endforeach
                         </ul>
                     </div>
                 </div>
                 @endif
                
                 {{-- Empty State --}}
                 @if(empty($results['tickets']) && empty($results['clients']) && empty($results['users']) && empty($results['articles']))
                    <div class="text-center py-10 text-neutral-500">
                        No results found for "{{ $query }}".
                    </div>
                 @endif

            </div>
        </div>
    </div>
</x-app-layout>
