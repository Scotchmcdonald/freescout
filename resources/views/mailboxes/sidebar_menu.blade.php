{{-- Mailbox Sidebar Menu - Settings Section --}}
<div class="bg-white border-r border-gray-200 w-64 flex flex-col">
    {{-- Mailbox Header with Dropdown --}}
    <div class="p-4 border-b border-gray-200">
        @php
            $menu_mailboxes = auth()->user()->isAdmin() 
                ? \App\Models\Mailbox::all()->sortBy('name') 
                : auth()->user()->mailboxes->sortBy('name');
        @endphp
        
        {{-- Mailbox Name Dropdown --}}
        <div class="relative">
            @if (count($menu_mailboxes) > 1)
                <button type="button" 
                        class="w-full text-left flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onclick="this.nextElementSibling.classList.toggle('hidden')">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 truncate">{{ $mailbox->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $mailbox->email }}</div>
                    </div>
                    <svg class="ml-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                {{-- Dropdown Menu --}}
                <div class="hidden absolute z-10 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 max-h-96 overflow-y-auto">
                    @foreach ($menu_mailboxes as $mailbox_item)
                        <a href="{{ route(Route::currentRouteName(), ['mailbox' => $mailbox_item->id]) }}" 
                           class="block px-4 py-2 text-sm {{ $mailbox_item->id == $mailbox->id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ $mailbox_item->name }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-3 py-2">
                    <div class="font-semibold text-gray-900">{{ $mailbox->name }}</div>
                    <div class="text-xs text-gray-500">{{ $mailbox->email }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Settings Menu Items --}}
    <nav class="flex-1 px-2 py-4 space-y-1">
        @if (Auth::user()->can('update', $mailbox))
            @if (Auth::user()->isAdmin())
                <a href="{{ route('mailboxes.settings', $mailbox) }}" 
                   class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ Route::currentRouteName() == 'mailboxes.settings' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="mr-3 h-5 w-5 {{ Route::currentRouteName() == 'mailboxes.settings' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('Edit Mailbox') }}
                </a>

                <a href="{{ route('mailboxes.connection.incoming', $mailbox) }}" 
                   class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ in_array(Route::currentRouteName(), ['mailboxes.connection.incoming', 'mailboxes.connection.outgoing']) ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="mr-3 h-5 w-5 {{ in_array(Route::currentRouteName(), ['mailboxes.connection.incoming', 'mailboxes.connection.outgoing']) ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    {{ __('Connection Settings') }}
                </a>
            @endif

            <a href="{{ route('mailboxes.permissions', $mailbox) }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ Route::currentRouteName() == 'mailboxes.permissions' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="mr-3 h-5 w-5 {{ Route::currentRouteName() == 'mailboxes.permissions' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('Permissions') }}
            </a>

            <a href="{{ route('mailboxes.auto_reply', $mailbox) }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ Route::currentRouteName() == 'mailboxes.auto_reply' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="mr-3 h-5 w-5 {{ Route::currentRouteName() == 'mailboxes.auto_reply' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                {{ __('Auto Reply') }}
            </a>
        @endif
    </nav>

    {{-- Open Mailbox Button --}}
    <div class="p-4 border-t border-gray-200">
        <a href="{{ route('mailboxes.view', $mailbox) }}" 
           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            {{ __('Open Mailbox') }}
        </a>
    </div>
</div>
