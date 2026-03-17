<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Customers') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-lg font-semibold">All Customers</h3>
                        
                        <form method="GET" action="{{ route('customers.index') }}" class="flex gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Search customers..."
                                   class="border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                                Search
                            </button>
                        </form>
                    </div>
                    
                    @if($customers->isEmpty())
                        <div class="text-center py-12 text-neutral-500">
                            <p>No customers found.</p>
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
                                            Company
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Conversations
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($customers as $customer)
                                        <tr class="hover:bg-neutral-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-neutral-900">
                                                    {{ $customer->getFullName() }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-neutral-600">
                                                    {{ $customer->getMainEmail() }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-neutral-600">
                                                    {{ $customer->company ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium bg-primary-100 text-primary-800 rounded">
                                                    {{ $customer->conversations_count ?? 0 }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-600">
                                                {{ $customer->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('customers.show', $customer) }}" 
                                                   class="text-primary-600 hover:text-primary-900">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6">
                            {{ $customers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
