<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Permission Matrix') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />

            <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100" x-data="permissionMatrix()">
                    
                    <!-- Add Role Form -->
                    <div class="mb-4 p-4 border rounded bg-gray-50 dark:bg-gray-700">
                        <form method="POST" action="{{ route('rbac.roles.store') }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" name="name" placeholder="Role Name (e.g. manager)" class="rounded border-gray-300 dark:bg-gray-900 dark:border-gray-600 shadow-sm focus:ring-indigo-500" required>
                            <input type="text" name="label" placeholder="Display Label" class="rounded border-gray-300 dark:bg-gray-900 dark:border-gray-600 shadow-sm focus:ring-indigo-500">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 text-sm">Add Role</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permission</th>
                                    @foreach($roles as $role)
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider group">
                                            <div class="flex flex-col items-center">
                                                <span>{{ $role->label ?? $role->name }}</span>
                                                <span class="text-[10px] text-gray-400 normal-case">{{ $role->name }}</span>
                                                <form action="{{ route('rbac.roles.destroy', $role) }}" method="POST" class="mt-1" onsubmit="return confirm('Delete role {{ $role->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-400 hover:text-red-600 text-xs">[x]</button>
                                                </form>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $permission->label ?? $permission->name }}
                                            <div class="text-xs text-gray-500">{{ $permission->name }}</div>
                                        </td>
                                        @foreach($roles as $role)
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" 
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                       :checked="{{ $role->permissions->contains($permission->id) ? 'true' : 'false' }}"
                                                       @change="togglePermission({{ $role->id }}, {{ $permission->id }}, $event.target.checked)">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function permissionMatrix() {
            return {
                togglePermission(roleId, permissionId, attached) {
                    axios.post('{{ route('rbac.update') }}', {
                        role_id: roleId,
                        permission_id: permissionId,
                        attached: attached
                    })
                    .then(response => {
                        console.log('Updated');
                    })
                    .catch(error => {
                        console.error('Error updating permission');
                        alert('Failed to update permission');
                    });
                }
            }
        }
    </script>
</x-app-layout>
