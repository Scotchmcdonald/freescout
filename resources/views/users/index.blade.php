<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
                {{ __('Users') }}
            </h2>
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                {{ __('New User') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    @if($users->isEmpty())
                        <div class="text-center py-12 text-neutral-500">
                            <p>No users found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-neutral-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Mailboxes
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($users as $user)
                                        <tr class="hover:bg-neutral-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold">
                                                            {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name ?? '', 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-neutral-900">
                                                            {{ $user->getFullName() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-neutral-600">{{ $user->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($user->role == 2)
                                                    <span class="px-2 py-1 text-xs font-medium bg-primary-100 text-primary-800 rounded">Admin</span>
                                                @elseif($user->role == 3)
                                                    <span class="px-2 py-1 text-xs font-medium bg-primary-100 text-primary-800 rounded">Reporter</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-medium bg-neutral-100 text-neutral-800 rounded">User</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($user->status == 1)
                                                    <span class="px-2 py-1 text-xs font-medium bg-success-100 text-success-800 rounded">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-medium bg-danger-100 text-danger-800 rounded">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-600">
                                                {{ $user->mailboxes->count() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                                <a href="{{ route('users.show', $user) }}" class="text-primary-600 hover:text-primary-900">View</a>
                                                <a href="{{ route('users.edit', $user) }}" class="text-primary-600 hover:text-primary-900">Edit</a>
                                                @if($user->id !== auth()->id())
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-danger-600 hover:text-danger-900">Delete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
