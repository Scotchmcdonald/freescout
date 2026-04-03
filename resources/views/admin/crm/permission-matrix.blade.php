<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-neutral-800">
                    🔐 {{ __('Contact & Permission Matrix') }}
                </h2>
                <p class="text-sm text-neutral-500 mt-1">Manage client contact permissions and role assignments</p>
            </div>
            <div class="flex items-center space-x-3">
                <form method="GET" class="flex items-center space-x-2">
                    <select name="client_id" onchange="this.form.submit()"
                        class="rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">All Clients</option>
                        @foreach ($allClients as $client)
                            <option value="{{ $client->id }}"
                                {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="permissionMatrix()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Role Templates Quick Actions --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200"
                x-data="{
                    templateClient: '',
                    templateRole: '',
                    templateContactCount: 0,
                    submitting: false,
                    showConfirm: false,
                    clientContactCounts: {{ json_encode($allClients->mapWithKeys(fn($c) => [$c->id => $c->contacts_count ?? $c->contacts()->count()])) }},
                    updateContactCount() {
                        this.templateContactCount = this.templateClient ? (this.clientContactCounts[this.templateClient] ?? 0) : 0;
                    },
                    openConfirm() {
                        if (!this.templateClient || !this.templateRole) return;
                        this.showConfirm = true;
                    },
                    submitTemplate() {
                        this.submitting = true;
                        this.showConfirm = false;
                        this.$refs.templateForm.submit();
                    }
                }">
                <div class="border-l-4 p-6" style="border-color: var(--theme-primary-500, #6366f1)">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6" style="color: var(--theme-primary-600, #4f46e5)" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-lg font-medium text-neutral-900">⚡ Quick Role Templates</h3>
                            <p class="text-sm text-neutral-600 mt-1">Bulk-apply a permission role to <strong>all
                                    contacts</strong> under a specific client. Use with caution.</p>
                        </div>
                    </div>

                    <form x-ref="templateForm" action="{{ route('admin.crm.permission-matrix.apply-template') }}"
                        method="POST" class="mt-5">
                        @csrf
                        <input type="hidden" name="client_id" x-model="templateClient">
                        <input type="hidden" name="permission_type" x-model="templateRole">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            {{-- Client selector --}}
                            <div>
                                <label for="template-client"
                                    class="block text-sm font-medium text-neutral-700 mb-1">Select Client</label>
                                <select id="template-client" x-model="templateClient" @change="updateContactCount()"
                                    class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm">
                                    <option value="">— Choose a client —</option>
                                    @foreach ($allClients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Role selector --}}
                            <div>
                                <label for="template-role" class="block text-sm font-medium text-neutral-700 mb-1">Apply
                                    Role</label>
                                <select id="template-role" x-model="templateRole"
                                    class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm">
                                    <option value="">— Choose a role —</option>
                                    @foreach ($permissionTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Apply button + preview --}}
                            <div>
                                <p class="text-xs text-neutral-500 mb-2" x-show="templateClient">
                                    This will update <strong x-text="templateContactCount"></strong> contact(s).
                                </p>
                                <button type="button" @click="openConfirm()"
                                    :disabled="!templateClient || !templateRole || submitting"
                                    class="w-full px-4 py-2 rounded-md text-sm font-medium text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed"
                                    style="background-color: var(--theme-primary-600, #4f46e5)">
                                    <svg x-show="submitting" class="inline animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span x-text="submitting ? 'Applying…' : 'Apply to All Contacts'"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Confirmation modal --}}
                    <div x-show="showConfirm" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
                        <div class="absolute inset-0 bg-black/50" @click="showConfirm = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100">
                            <h3 class="text-base font-semibold text-neutral-900">Apply Role Template?</h3>
                            <p class="mt-2 text-sm text-neutral-600">
                                This will apply the <strong x-text="templateRole.replace('_',' ')"></strong> role to
                                <strong x-text="templateContactCount"></strong> contact(s). Existing roles will be
                                replaced.
                                This action is logged.
                            </p>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="showConfirm = false"
                                    class="px-4 py-2 bg-white border border-neutral-300 text-sm font-medium text-neutral-700 rounded-md hover:bg-neutral-50 transition">
                                    Cancel
                                </button>
                                <button type="button" @click="submitTemplate()"
                                    class="px-4 py-2 text-white text-sm font-semibold rounded-md shadow-sm transition"
                                    style="background-color: var(--theme-warning-600, #d97706)">
                                    Yes, Apply Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permission Matrix --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-neutral-900">📋 Permission Matrix</h3>
                            <p class="text-sm text-neutral-600 mt-1">Select role-based permissions for each contact.
                                Changes are saved in bulk.</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div x-show="selectedCount > 0" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="flex items-center px-3 py-1.5 rounded-md"
                                style="background-color: var(--theme-warning-50, #fffbeb); border: 1px solid var(--theme-warning-200, #fde68a)">
                                <svg class="h-4 w-4 mr-1.5" style="color: var(--theme-warning-600, #f59e0b)"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="text-sm font-medium" style="color: var(--theme-warning-800, #92400e)">
                                    <span x-text="selectedCount"></span> unsaved changes
                                </span>
                            </div>
                            <x-primary-button @click="saveChanges()" x-show="selectedCount > 0"
                                x-bind:disabled="saving" class="transition-all">
                                <svg class="-ml-1 mr-2 h-4 w-4" :class="saving && 'animate-spin'" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-show="!saving">Save Changes</span>
                                <span x-show="saving" x-cloak>Saving...</span>
                            </x-primary-button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider sticky left-0 bg-neutral-50 z-10">
                                    Contact
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Client
                                </th>
                                @foreach ($permissionTypes as $key => $label)
                                    <th scope="col"
                                        class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                        {{ $label }}
                                    </th>
                                @endforeach
                                <th scope="col"
                                    class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Current
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($clients as $client)
                                @foreach ($client->contacts as $contact)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-neutral-200 flex items-center justify-center text-neutral-600 font-bold">
                                                        {{ substr($contact->first_name, 0, 1) }}{{ substr($contact->last_name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-neutral-900">
                                                        {{ $contact->full_name }}
                                                        @if ($contact->is_primary)
                                                            <x-status-badge status="success" text="Primary" />
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-neutral-500">{{ $contact->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                            {{ $client->name }}
                                        </td>
                                        @foreach ($permissionTypes as $key => $label)
                                            <td class="px-4 py-4 text-center">
                                                <input type="radio" name="permission_{{ $contact->id }}"
                                                    value="{{ $key }}"
                                                    @change="updatePermission({{ $contact->id }}, '{{ $key }}')"
                                                    {{ $contact->permissions->where('permission_type', $key)->isNotEmpty() ? 'checked' : '' }}
                                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-neutral-300">
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-4 text-center text-sm">
                                            @php
                                                $currentPerm = $contact->permissions->first();
                                            @endphp
                                            @if ($currentPerm)
                                                <x-status-badge :status="'success'" :text="$permissionTypes[$currentPerm->permission_type] ?? 'None'" />
                                            @else
                                                <x-status-badge status="warning" text="None" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ count($permissionTypes) + 3 }}" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="rounded-full p-3"
                                                style="background-color: var(--theme-primary-50, #eef2ff)">
                                                <svg class="h-12 w-12"
                                                    style="color: var(--theme-primary-400, #818cf8)" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <h3 class="mt-4 text-base font-medium text-neutral-900">No contacts
                                                available</h3>
                                            <p class="mt-1 text-sm text-neutral-500">Get started by adding contacts to
                                                your clients in the CRM module.</p>
                                            <div class="mt-6">
                                                <a href="{{ route('admin.crm.clients.index') }}"
                                                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white transition-colors"
                                                    style="background-color: var(--theme-primary-600, #4f46e5)"
                                                    onmouseover="this.style.backgroundColor='var(--theme-primary-700, #4338ca)'"
                                                    onmouseout="this.style.backgroundColor='var(--theme-primary-600, #4f46e5)'">
                                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Go to CRM
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Permission Legend --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-neutral-900 mb-1">What each role can do</h3>
                <p class="text-sm text-neutral-500 mb-4">Use this reference when assigning roles so your clients
                    understand what access they are granting.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($permissionTypes as $key => $label)
                        <div class="border border-neutral-200 rounded-lg p-4">
                            <h4 class="font-semibold text-neutral-900 mb-1">{{ $label }}</h4>
                            @if (isset($permissionDescriptions[$key]))
                                <p class="text-xs text-neutral-500 mb-3">{{ $permissionDescriptions[$key] }}</p>
                            @endif
                            <ul class="text-sm text-neutral-600 space-y-2">
                                @php
                                    $allActionKeys = array_keys($actionDescriptions);
                                    $grantedKeys = \Modules\Crm\Models\ContactPermission::getActionsByType($key);
                                @endphp
                                @foreach ($allActionKeys as $actionKey)
                                    @php $granted = in_array($actionKey, $grantedKeys, true); @endphp
                                    <li class="flex items-start {{ $granted ? '' : 'opacity-40' }}">
                                        @if ($granted)
                                            <svg class="h-4 w-4 text-success-500 mr-2 mt-0.5 flex-shrink-0"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4 text-neutral-300 mr-2 mt-0.5 flex-shrink-0"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @endif
                                        <span>{{ $actionDescriptions[$actionKey] ?? $actionKey }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('permissionMatrix', () => ({
                    changes: {},
                    saving: false,

                    get selectedCount() {
                        return Object.keys(this.changes).length;
                    },

                    updatePermission(contactId, permissionType) {
                        this.changes[contactId] = permissionType;
                    },

                    saveChanges() {
                        if (this.saving) return;

                        if (!confirm(
                                `Save ${this.selectedCount} permission change(s)? This will update contact access immediately.`
                                )) {
                            return;
                        }

                        this.saving = true;
                        const updates = Object.entries(this.changes).map(([contactId, permissionType]) => ({
                            contact_id: parseInt(contactId),
                            permission_type: permissionType
                        }));

                        fetch('{{ route('admin.crm.permission-matrix.bulk-update') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    updates
                                })
                            })
                            .then(response => {
                                if (response.ok) {
                                    // Show success feedback before reload
                                    const alert = document.createElement('div');
                                    alert.className =
                                        'fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
                                    alert.style.cssText =
                                        'background-color: var(--theme-success-500, #10b981); color: white;';
                                    alert.innerHTML =
                                        '<svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Permissions updated successfully</span>';
                                    document.body.appendChild(alert);
                                    setTimeout(() => window.location.reload(), 800);
                                } else {
                                    this.saving = false;
                                    alert(
                                        '❌ Failed to update permissions. Please check your connection and try again.');
                                }
                            })
                            .catch(error => {
                                this.saving = false;
                                console.error('Error:', error);
                                alert(
                                    '❌ Network error occurred. Please check your connection and try again.');
                            });
                    }
                }));
            });
        </script>
    @endpush
</x-app-layout>
