<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Roles & Permissions') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />

            <div class="flex-1 min-w-0" x-data="rbacMatrix({{ Js::from($matrix) }}, '{{ route('rbac.update') }}', '{{ csrf_token() }}')">

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Top Bar: Search + Add Role --}}
                <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text"
                               x-model="search"
                               placeholder="Search permissions..."
                               class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400">
                    </div>

                    {{-- Add Role --}}
                    <div x-data="{ showForm: false }">
                        <button @click="showForm = !showForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Role
                        </button>

                        <div x-show="showForm" x-transition x-cloak
                             class="absolute right-0 mt-2 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-600 z-50 min-w-[320px]">
                            <form method="POST" action="{{ route('rbac.roles.store') }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Role Name (unique key)</label>
                                    <input type="text" name="name" placeholder="e.g. MSP Manager"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm shadow-sm focus:ring-blue-500" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Display Label</label>
                                    <input type="text" name="label" placeholder="e.g. MSP Manager"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm shadow-sm focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Scope</label>
                                    <select name="scope" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm shadow-sm focus:ring-blue-500">
                                        <option value="internal">Internal (MSP Staff)</option>
                                        <option value="client">Client (External)</option>
                                    </select>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button type="submit" class="flex-1 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                        Create Role
                                    </button>
                                    <button type="button" @click="showForm = false"
                                            class="px-3 py-1.5 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Toast Notification --}}
                <div x-show="toast" x-transition x-cloak
                     class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium"
                     :class="toast?.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'">
                    <span x-text="toast?.message"></span>
                </div>

                {{-- Permission Matrix — Single Table for proper column alignment --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div>
                            <table class="w-full border-collapse">
                                {{-- Role Column Headers (sticky) --}}
                                <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                    <tr class="border-b-2 border-gray-200 dark:border-gray-600">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[240px]">
                                            Permission
                                        </th>
                                        @foreach($roles as $role)
                                            <x-rbac-role-header :role="$role" />
                                        @endforeach
                                    </tr>
                                </thead>

                                {{-- Module groups as tbody sections --}}
                                @foreach($groupedPermissions as $moduleKey => $modulePermissions)
                                    @php
                                        $moduleLabel = $moduleLabels[$moduleKey] ?? ucfirst($moduleKey);
                                        $permCount = $modulePermissions->count();
                                        $modulePermIds = $modulePermissions->pluck('id')->toArray();
                                        $modulePermNames = $modulePermissions->pluck('name')->toArray();
                                    @endphp

                                    <tbody x-show="moduleMatchesSearch({{ Js::from($modulePermNames) }})"
                                           x-data="{ open: false }">
                                        {{-- Module accordion header row --}}
                                        <tr class="bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-200 dark:border-gray-600 cursor-pointer select-none">
                                            <td class="px-4 py-2.5" @click="open = !open">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                                                         :class="{ 'rotate-90': open }"
                                                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">
                                                        {{ $moduleLabel }}
                                                    </span>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                                        ({{ $permCount }})
                                                    </span>
                                                </div>
                                            </td>
                                            {{-- Per-role tri-state checkboxes for this module --}}
                                            @foreach($roles as $role)
                                                <td class="px-3 py-2.5 text-center">
                                                    @if($role->is_super_admin)
                                                        <span class="inline-flex items-center justify-center w-5 h-5 text-green-500 dark:text-green-400"
                                                              title="Super Admin — all permissions granted">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                    @else
                                                        @php $triKey = $role->id . '-' . $moduleKey; @endphp
                                                        <label class="relative inline-flex items-center cursor-pointer"
                                                               title="Toggle all {{ $moduleLabel }} permissions for {{ $role->label ?? $role->name }}"
                                                               @click.stop>
                                                            <input type="checkbox"
                                                                   class="sr-only peer"
                                                                   :checked="moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'all'"
                                                                   @change="toggleModuleForRole({{ Js::from($modulePermIds) }}, {{ $role->id }})">
                                                            {{-- Tri-state visual: full check, indeterminate dash, or empty --}}
                                                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all duration-150"
                                                                 :class="{
                                                                     'border-blue-500 dark:border-blue-400 bg-blue-500 dark:bg-blue-400': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'all',
                                                                     'border-blue-400 dark:border-blue-500 bg-blue-200 dark:bg-blue-700': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'some',
                                                                     'border-gray-300 dark:border-gray-500 hover:border-blue-400': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'none',
                                                                 }">
                                                                {{-- Checkmark for 'all' --}}
                                                                <svg x-show="moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'all'"
                                                                     class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                </svg>
                                                                {{-- Dash for 'some' --}}
                                                                <svg x-show="moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'some'"
                                                                     class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                                                </svg>
                                                            </div>
                                                        </label>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Permission rows (shown when expanded) --}}
                                        @foreach($modulePermissions as $permission)
                                            <x-rbac-permission-row
                                                :permission="$permission"
                                                :roles="$roles" />
                                        @endforeach
                                    </tbody>
                                @endforeach
                            </table>
                        </div>

                        {{-- Empty State --}}
                        @if($groupedPermissions->isEmpty())
                            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <svg class="mx-auto w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <p class="text-sm">No permissions found. Run <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-xs">php artisan db:seed --class=RbacSeeder</code> to initialize.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Legend --}}
                <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400 px-1">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Super Admin — all permissions (non-revocable)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 inline-flex items-center justify-center rounded border-2 border-blue-500 bg-blue-500">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        Permission granted
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 inline-flex items-center justify-center rounded border-2 border-blue-400 bg-blue-200 dark:bg-blue-700">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                            </svg>
                        </span>
                        Some permissions in group
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-500"></span>
                        Permission denied
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
