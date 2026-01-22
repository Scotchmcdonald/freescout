<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ $client->name }} - {{ __('Client 360') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ activeTab: 'overview', showContactModal: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Tabs Navigation --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-t-lg border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button @click="activeTab = 'overview'" 
                            :class="{'border-indigo-500 text-indigo-600': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'overview'}"
                            class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        Overview
                    </button>
                    @if($assetWidgets->isNotEmpty())
                    <button @click="activeTab = 'assets'"
                            :class="{'border-indigo-500 text-indigo-600': activeTab === 'assets', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'assets'}"
                            class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        Assets
                    </button>
                    @endif
                    @if($financialWidgets->isNotEmpty())
                    <button @click="activeTab = 'billing'"
                            :class="{'border-indigo-500 text-indigo-600': activeTab === 'billing', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'billing'}"
                            class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        Billing
                    </button>
                    @endif
                    <button @click="activeTab = 'contacts'"
                            dusk="contacts-tab"
                            :class="{'border-indigo-500 text-indigo-600': activeTab === 'contacts', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'contacts'}"
                            class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        Contacts
                    </button>
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-b-lg p-6 min-h-[400px]">
                
                {{-- Overview Tab --}}
                <div x-show="activeTab === 'overview'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Vitals Card --}}
                        <div class="col-span-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Client Vitals</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs text-gray-500">Tier</dt>
                                    <dd class="text-sm font-semibold text-gray-900">{{ $client->tier ?? 'Standard' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Status</dt>
                                    <dd class="text-sm font-semibold">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {{ ucfirst($client->status ?? 'Active') }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Primary Email</dt>
                                    <dd class="text-sm text-gray-900">{{ $client->email ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Phone</dt>
                                    <dd class="text-sm text-gray-900">{{ $client->phone ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="col-span-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Quick Actions</h3>
                            <div class="space-y-2">
                                @php
                                    $primaryContact = $contacts->where('is_primary', true)->first();
                                    $primaryUser = $primaryContact && $primaryContact->user_id ? \App\Models\User::find($primaryContact->user_id) : null;
                                @endphp
                                
                                @if($primaryUser && auth()->user()->can('impersonate', $primaryUser))
                                    <form method="POST" action="{{ route('impersonate', $primaryUser->id) }}">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View as Customer
                                        </button>
                                    </form>
                                @endif
                                
                                <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Edit Profile
                                </button>
                                <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Manage Contracts
                                </button>
                                @if(Module::isEnabled('GoogleAdmin'))
                                <a href="{{ route('google-admin.settings.edit', $client) }}" class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Google Workspace
                                </a>
                                @endif
                                <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Suspend Account
                                </button>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="col-span-1 grid grid-cols-2 gap-4">
                            @if($assetWidgets->isNotEmpty())
                                <div class="bg-blue-50 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-blue-700">{{ $client->assets()->count() }}</div>
                                    <div class="text-xs font-medium text-blue-500 uppercase">Assets</div>
                                </div>
                            @endif
                            @if($financialWidgets->isNotEmpty())
                                <div class="bg-green-50 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-green-700">{{ $client->invoices()->count() }}</div>
                                    <div class="text-xs font-medium text-green-500 uppercase">Invoices</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Sidebar Widgets (Dynamic) --}}
                    @if($sidebarWidgets->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             @foreach($sidebarWidgets as $widget)
                                {!! $widget !!}
                            @endforeach
                        </div>
                    @endif

                    {{-- Custom Fields --}}
                    @includeIf('crm::partials.custom_fields_renderer', ['entity' => $client])
                </div>



                {{-- Assets Tab (Dynamic Widgets) --}}
                <div x-show="activeTab === 'assets'" style="display: none;">
                    @foreach($assetWidgets as $widget)
                        {!! $widget !!}
                    @endforeach
                </div>

                {{-- Billing Tab (Dynamic Widgets) --}}
                <div x-show="activeTab === 'billing'" style="display: none;">
                    @foreach($financialWidgets as $widget)
                        {!! $widget !!}
                    @endforeach
                </div>

                {{-- Contacts Tab --}}
                <div x-show="activeTab === 'contacts'" style="display: none;">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Organization Contacts</h3>
                        <button @click="showContactModal = true" dusk="add-contact" class="text-sm text-indigo-600 hover:text-indigo-900">Add Contact</button>
                    </div>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($contacts as $contact)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                                @if($contact->is_primary)
                                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Primary</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $contact->role ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $contact->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $contact->phone ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No contacts found for this client.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- Add Contact Modal --}}
        <div x-show="showContactModal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form action="{{ route('crm.contacts.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Add New Contact
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                            <input type="text" name="first_name" id="first_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                            <input type="text" name="last_name" id="last_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input type="text" name="phone" id="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                        <input type="text" name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="is_primary" name="is_primary" type="checkbox" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="is_primary" class="font-medium text-gray-700">Primary Contact</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Contact
                            </button>
                            <button type="button" @click="showContactModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
