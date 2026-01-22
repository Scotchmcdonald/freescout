<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
                    {{ __('Edit Asset') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $asset->serial_number }}</p>
            </div>
            <a href="{{ route('admin.assets.show', $asset->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-800 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Cancel') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            
            @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.assets.update', $asset->id) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-6">
                        <div>
                            <label for="serial_number" class="block text-sm font-medium text-gray-700">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number" dusk="serial-number"
                                   value="{{ old('serial_number', $asset->serial_number) }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="hostname" class="block text-sm font-medium text-gray-700">Hostname</label>
                            <input type="text" name="hostname" id="hostname"
                                   value="{{ old('hostname', $asset->hostname) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="asset_type" class="block text-sm font-medium text-gray-700">Asset Type</label>
                            <select name="asset_type" id="asset_type" dusk="asset-type" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="windows" {{ old('asset_type', $asset->asset_type) === 'windows' ? 'selected' : '' }}>Windows</option>
                                <option value="chromebook" {{ old('asset_type', $asset->asset_type) === 'chromebook' ? 'selected' : '' }}>Chromebook</option>
                                <option value="mac" {{ old('asset_type', $asset->asset_type) === 'mac' ? 'selected' : '' }}>Mac</option>
                                <option value="linux" {{ old('asset_type', $asset->asset_type) === 'linux' ? 'selected' : '' }}>Linux</option>
                                <option value="other" {{ old('asset_type', $asset->asset_type) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-700">Model</label>
                            <input type="text" name="model" id="model" dusk="model"
                                   value="{{ old('model', $asset->procurement_metadata['model'] ?? '') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" dusk="status" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($validStatuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $asset->status) === $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @if($asset->status === 'retired')
                                <p class="mt-1 text-xs text-red-600">Warning: Retired assets cannot be transitioned to other statuses.</p>
                            @endif
                        </div>

                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select name="client_id" id="client_id" dusk="client" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $asset->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assigned_user_email" class="block text-sm font-medium text-gray-700">Assigned User Email</label>
                            <input type="email" name="assigned_user_email" id="assigned_user_email"
                                   value="{{ old('assigned_user_email', $asset->assigned_user_email) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="user@example.com">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('admin.assets.show', $asset->id) }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" dusk="save"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
