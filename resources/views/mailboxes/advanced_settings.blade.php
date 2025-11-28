<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Advanced Settings: {{ $mailbox->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('mailboxes.sidebar_menu', ['mailbox' => $mailbox])
            
            <form method="POST" action="{{ route('mailboxes.save_advanced_settings', $mailbox) }}" class="space-y-6">
                @csrf
                
                <!-- Email Aliases -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Email Aliases</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Add additional email addresses that this mailbox can send from (one per line).
                        </p>
                        
                        <div class="mb-4">
                            <label for="aliases" class="block text-sm font-medium text-gray-700">Aliases</label>
                            <textarea 
                                id="aliases" 
                                name="aliases" 
                                rows="4" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="alias1@example.com&#10;alias2@example.com">{{ str_replace(',', "\n", $mailbox->aliases ?? '') }}</textarea>
                            @error('aliases')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="aliases_reply" 
                                    value="1" 
                                    {{ $mailbox->aliases_reply ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">
                                    Use alias in reply when original email was sent to alias
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- From Name Options -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">From Name</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Choose how the "From" name appears in outgoing emails.
                        </p>
                        
                        <div class="space-y-2 mb-4" x-data="advancedMailboxSettings()">
                            @foreach($fromNameOptions as $value => $label)
                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        name="from_name" 
                                        value="{{ $value }}" 
                                        {{ ($mailbox->from_name ?? 1) == $value ? 'checked' : '' }}
                                        class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                        @change="toggleCustomFromName()">
                                    <span class="ml-2 text-sm text-gray-600">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        
                        <div id="custom_from_name_field" class="mb-4 {{ ($mailbox->from_name ?? 1) == 4 ? '' : 'hidden' }}">
                            <label for="from_name_custom" class="block text-sm font-medium text-gray-700">Custom Name</label>
                            <input 
                                type="text" 
                                id="from_name_custom" 
                                name="from_name_custom" 
                                value="{{ $mailbox->from_name_custom ?? '' }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Company Support">
                            @error('from_name_custom')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Assignment Options -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Ticket Assignment</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Configure how new tickets are assigned.
                        </p>
                        
                        <div class="mb-4">
                            <label for="ticket_assignee" class="block text-sm font-medium text-gray-700">Default Assignee for New Tickets</label>
                            <select 
                                id="ticket_assignee" 
                                name="ticket_assignee" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($ticketAssigneeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($mailbox->ticket_assignee ?? 1) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('ticket_assignee')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Before Reply Text -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Before Reply Text</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Text added before the reply when forwarding or replying.
                        </p>
                        
                        <div class="mb-4">
                            <label for="before_reply" class="block text-sm font-medium text-gray-700">Before Reply/Forward</label>
                            <textarea 
                                id="before_reply" 
                                name="before_reply" 
                                rows="3" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="--- Original Message ---">{{ $mailbox->before_reply ?? '' }}</textarea>
                            @error('before_reply')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Signature -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Signature</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Default email signature for this mailbox. Supports basic HTML.
                        </p>
                        
                        <div class="mb-4">
                            <label for="signature" class="block text-sm font-medium text-gray-700">Email Signature</label>
                            <textarea 
                                id="signature" 
                                name="signature" 
                                rows="6" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Best regards,&#10;Support Team">{{ $mailbox->signature ?? '' }}</textarea>
                            @error('signature')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Variables: {{ '{' }}{{ '%' }}user.first_name{{ '%' }}{{ '}' }}, {{ '{' }}{{ '%' }}user.last_name{{ '%' }}{{ '}' }}, {{ '{' }}{{ '%' }}mailbox.name{{ '%' }}{{ '}' }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Satisfaction Ratings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Satisfaction Ratings</h3>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="ratings" 
                                    value="1" 
                                    {{ $mailbox->ratings ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">
                                    Enable satisfaction ratings for this mailbox
                                </span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">
                                Customers can rate their support experience after a conversation is closed.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
