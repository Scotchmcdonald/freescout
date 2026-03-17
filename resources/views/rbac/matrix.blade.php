<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 dark:text-neutral-200 leading-tight">
            {{ __('Roles & Permissions') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />

            <div class="flex-1 min-w-0" x-data="rbacMatrix({{ Js::from($matrix) }}, '{{ route('rbac.update') }}', '{{ csrf_token() }}')">

                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 text-success-700 dark:text-success-400 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 text-danger-700 dark:text-danger-400 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Top Bar: Search + Add Role --}}
                <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-md">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text"
                               x-model="search"
                               placeholder="Search permissions..."
                               class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-400">
                    </div>

                    {{-- Add Role --}}
                    <div x-data="{ showForm: false }">
                        <button @click="showForm = !showForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Role
                        </button>

                        <div x-show="showForm" x-transition x-cloak
                             class="absolute right-0 mt-2 p-4 bg-white dark:bg-neutral-800 rounded-lg shadow-xl border border-neutral-200 dark:border-neutral-600 z-50 min-w-[320px]">
                            <form method="POST" action="{{ route('rbac.roles.store') }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Role Name (unique key)</label>
                                    <input type="text" name="name" placeholder="e.g. MSP Manager"
                                           class="w-full rounded-md border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 text-sm shadow-sm focus:ring-primary-500" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Display Label</label>
                                    <input type="text" name="label" placeholder="e.g. MSP Manager"
                                           class="w-full rounded-md border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 text-sm shadow-sm focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Scope</label>
                                    <select name="scope" class="w-full rounded-md border-neutral-300 dark:border-neutral-600 dark:bg-neutral-700 text-sm shadow-sm focus:ring-primary-500">
                                        <option value="internal">Internal (MSP Staff)</option>
                                        <option value="client">Client (External)</option>
                                    </select>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button type="submit" class="flex-1 px-3 py-1.5 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                                        Create Role
                                    </button>
                                    <button type="button" @click="showForm = false"
                                            class="px-3 py-1.5 text-sm font-medium rounded-md bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors">
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
                     :class="toast?.type === 'error' ? 'bg-danger-600 text-white' : 'bg-success-600 text-white'">
                    <span x-text="toast?.message"></span>
                </div>

                {{-- Permission Matrix — Single Table for proper column alignment --}}
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6">
                        <div>
                            <table class="w-full border-collapse">
                                {{-- Role Column Headers (sticky) --}}
                                <thead class="sticky top-0 z-10 bg-white dark:bg-neutral-800">
                                    <tr class="border-b-2 border-neutral-200 dark:border-neutral-600">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider min-w-[240px]">
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
                                        <tr class="bg-neutral-50 dark:bg-neutral-700/50 border-t-2 border-neutral-200 dark:border-neutral-600 cursor-pointer select-none">
                                            <td class="px-4 py-2.5" @click="open = !open">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-neutral-500 dark:text-neutral-400 transition-transform duration-200"
                                                         :class="{ 'rotate-90': open }"
                                                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <span class="font-semibold text-sm text-neutral-800 dark:text-neutral-200">
                                                        {{ $moduleLabel }}
                                                    </span>
                                                    <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                                        ({{ $permCount }})
                                                    </span>
                                                </div>
                                            </td>
                                            {{-- Per-role tri-state checkboxes for this module --}}
                                            @foreach($roles as $role)
                                                <td class="px-3 py-2.5 text-center">
                                                    @if($role->is_super_admin)
                                                        <span class="inline-flex items-center justify-center w-5 h-5 text-success-500 dark:text-success-400"
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
                                                                     'border-primary-500 dark:border-primary-400 bg-primary-500 dark:bg-primary-400': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'all',
                                                                     'border-primary-400 dark:border-primary-500 bg-primary-200 dark:bg-primary-700': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'some',
                                                                     'border-neutral-300 dark:border-neutral-500 hover:border-primary-400': moduleTriState({{ $role->id }}, {{ Js::from($modulePermIds) }}) === 'none',
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
                            <div class="text-center py-12 text-neutral-400 dark:text-neutral-500">
                                <svg class="mx-auto w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <p class="text-sm">No permissions found. Run <code class="bg-neutral-100 dark:bg-neutral-700 px-1.5 py-0.5 rounded text-xs">php artisan db:seed --class=RbacSeeder</code> to initialize.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Legend --}}
                <div class="mt-4 flex flex-wrap gap-4 text-xs text-neutral-500 dark:text-neutral-400 px-1">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-success-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Super Admin — all permissions (non-revocable)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 inline-flex items-center justify-center rounded border-2 border-primary-500 bg-primary-500">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        Permission granted
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 inline-flex items-center justify-center rounded border-2 border-primary-400 bg-primary-200 dark:bg-primary-700">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                            </svg>
                        </span>
                        Some permissions in group
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded border-2 border-neutral-300 dark:border-neutral-500"></span>
                        Permission denied
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
