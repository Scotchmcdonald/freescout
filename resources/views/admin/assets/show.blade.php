<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
                    {{ __('Asset Details') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $asset->serial_number }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.assets.edit', $asset->id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('admin.assets.inventory') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-800 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    {{ __('Back to Inventory') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Asset Information --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Asset Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="divide-y divide-gray-200">
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                                    <dd class="text-sm text-gray-900 font-mono">{{ $asset->serial_number }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Hostname</dt>
                                    <dd class="text-sm text-gray-900">{{ $asset->hostname ?: 'Not set' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Asset Type</dt>
                                    <dd class="text-sm text-gray-900">{{ ucfirst($asset->asset_type) }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Model</dt>
                                    <dd class="text-sm text-gray-900">{{ $asset->procurement_metadata['model'] ?? 'Not specified' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="divide-y divide-gray-200">
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Client</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($asset->client)
                                            <a href="{{ route('admin.clients.show', $asset->client_id) }}" class="text-primary-600 hover:underline">
                                                {{ $asset->client->name }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                    <dd class="text-sm text-gray-900">{{ $asset->assigned_user_email ?: 'Unassigned' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Source</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $asset->source === 'GoogleAdmin' ? 'bg-blue-100 text-blue-800' : 
                                               ($asset->source === 'Action1' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ $asset->source }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="text-sm text-gray-900">{{ $asset->updated_at->format('Y-m-d H:i:s') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Management --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status Management</h3>
                    
                    <div class="flex items-center space-x-4">
                        <div>
                            <span class="text-sm text-gray-500 mr-2">Current Status:</span>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                {{ $asset->status === 'active' ? 'bg-green-100 text-green-800' : 
                                   ($asset->status === 'inactive' ? 'bg-gray-100 text-gray-800' : 
                                   ($asset->status === 'retired' ? 'bg-red-100 text-red-800' :
                                   ($asset->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'))) }}">
                                {{ ucfirst($asset->status) }}
                            </span>
                        </div>
                        
                        @if(count($validTargetStatuses) > 0)
                            <form action="{{ route('admin.assets.update_status', $asset->id) }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                @method('PATCH')
                                <label for="status" class="text-sm text-gray-500">Change to:</label>
                                <select name="status" id="status" dusk="status" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                    @foreach($validTargetStatuses as $status)
                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" dusk="save" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Save
                                </button>
                            </form>
                        @else
                            <span class="text-sm text-gray-500 italic">
                                @if($asset->status === 'retired')
                                    Retired assets cannot be transitioned to other statuses.
                                @else
                                    No valid status transitions available.
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($asset->status === 'retired')
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-700">
                                <strong>Note:</strong> This asset has been retired and is in a terminal state. 
                                Any software license assignments were automatically revoked upon retirement.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Procurement Metadata --}}
            @if(!empty($asset->procurement_metadata))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Procurement Metadata</h3>
                    <pre class="bg-gray-50 p-4 rounded-md text-sm overflow-x-auto">{{ json_encode($asset->procurement_metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
