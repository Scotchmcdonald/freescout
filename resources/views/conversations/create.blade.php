<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if(request()->is('helpdesk/*'))
                Create Ticket
            @else
                New Conversation - {{ $mailbox->name }}
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ request()->is('helpdesk/*') ? route('helpdesk.tickets.store') : route('conversations.store', $mailbox) }}" id="conversation-form">
                        @csrf
                        
                        <!-- Hidden 'to' field for validation -->
                        <input type="hidden" name="to[]" id="to-field" value="">
                        
                        @if($errors->any())
                            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                                <ul class="list-disc list-inside text-sm text-red-700">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="space-y-6">
                            @if(request()->is('helpdesk/*'))
                                <!-- Client Selection for Helpdesk Tickets -->
                                <div>
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Client *
                                    </label>
                                    <select name="client_id" id="client_id" required
                                           dusk="client-select"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select Client</option>
                                        @foreach(\Modules\Crm\Models\Client::orderBy('name')->get() as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            
                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Customer Email *
                                </label>
                                <input type="email" name="customer_email" id="customer_email" required
                                       value="{{ old('customer_email') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="customer@example.com">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="customer_first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        First Name
                                    </label>
                                    <input type="text" name="customer_first_name" id="customer_first_name"
                                           value="{{ old('customer_first_name') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="customer_last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Last Name
                                    </label>
                                    <input type="text" name="customer_last_name" id="customer_last_name"
                                           value="{{ old('customer_last_name') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subject *
                                </label>
                                <input type="text" name="subject" id="subject" required
                                       value="{{ old('subject') }}"
                                       dusk="ticket-subject"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="What is this conversation about?">
                            </div>
                            
                            <div>
                                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">
                                    @if(request()->is('helpdesk/*'))
                                        Description *
                                    @else
                                        Message *
                                    @endif
                                </label>
                                <textarea name="{{ request()->is('helpdesk/*') ? 'description' : 'body' }}" 
                                          id="body" rows="10" required
                                          dusk="ticket-description"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Type your message...">{{ old('body', old('description')) }}</textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Status *
                                    </label>
                                    <select name="status" id="status" required
                                            dusk="status-select"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Closed</option>
                                        <option value="3" {{ old('status') == 3 ? 'selected' : '' }}>Pending</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="assign_to" class="block text-sm font-medium text-gray-700 mb-2">
                                        Assign To
                                    </label>
                                    <select name="assign_to" id="assign_to"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Unassigned</option>
                                        @foreach($mailbox->users as $user)
                                            <option value="{{ $user->id }}" {{ old('assign_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->getFullName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            @if(request()->is('helpdesk/*'))
                                <!-- Billing Fields for Helpdesk Tickets -->
                                <div class="border-t border-gray-200 pt-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Information</h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="billable" value="1" 
                                                       dusk="billable-checkbox"
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       {{ old('billable') ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700">Billable</span>
                                            </label>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="billable_hours" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Hours
                                                </label>
                                                <input type="number" name="billable_hours" id="billable_hours"
                                                       dusk="billable-hours"
                                                       step="0.5" min="0" value="{{ old('billable_hours') }}"
                                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </div>
                                            
                                            <div>
                                                <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Hourly Rate
                                                </label>
                                                <input type="number" name="hourly_rate" id="hourly_rate"
                                                       dusk="billable-rate"
                                                       step="0.01" min="0" value="{{ old('hourly_rate', '120.00') }}"
                                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ route('mailboxes.view', $mailbox) }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" 
                                    dusk="create-ticket-button"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Create Conversation
                            </button>
                        </div>
                    </form>
                    
                    <script>
                        // Sync customer_email to 'to' field on form submission
                        document.getElementById('conversation-form').addEventListener('submit', function(e) {
                            const emailInput = document.getElementById('customer_email');
                            const toField = document.getElementById('to-field');
                            if (emailInput && toField && emailInput.value) {
                                toField.value = emailInput.value;
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
