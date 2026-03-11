@extends('tsdm::layouts.master')

@section('module-content')

<nav class="text-xs text-gray-400 mb-1">
    <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
    <span class="mx-1">/</span>
    <a href="{{ route('tsdm.deployments.index') }}" class="hover:text-primary-600">Deployments</a>
    <span class="mx-1">/</span>
    <span class="text-gray-600">New Deployment</span>
</nav>
<h1 class="text-2xl font-bold text-gray-900 mb-8">Register Deployment</h1>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('tsdm.deployments.store') }}" class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
        @csrf

        <div class="px-6 py-5 space-y-5">

            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client
                    <span class="text-red-500">*</span>
                </label>
                @if ($clients->isNotEmpty())
                <select name="client_id" required
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">— Select client —</option>
                    @foreach ($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                    @endforeach
                </select>
                @else
                <input type="number" name="client_id" value="{{ old('client_id') }}" required
                       placeholder="CRM Client ID"
                       class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="text-xs text-gray-400 mt-1">CRM module not detected — enter ID manually.</p>
                @endif
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Deployment Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Acme Corp — Production"
                       class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            {{-- Environment + Git Provider (two-column) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment <span class="text-red-500">*</span></label>
                    <select name="environment" required
                            class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                        @foreach (['production', 'staging', 'development'] as $env)
                        <option value="{{ $env }}" {{ old('environment', 'production') === $env ? 'selected' : '' }}>
                            {{ ucfirst($env) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Git Provider <span class="text-red-500">*</span></label>
                    <select name="git_provider" required
                            class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                        @foreach (['gitlab' => 'GitLab', 'github' => 'GitHub'] as $val => $label)
                        <option value="{{ $val }}" {{ old('git_provider', config('tsdm.git.provider', 'gitlab')) === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Git Project ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Git Project ID</label>
                <input type="text" name="git_project_id" value="{{ old('git_project_id') }}"
                       placeholder="GitLab project ID or GitHub owner/repo"
                       class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                <p class="text-xs text-gray-400 mt-1">Overrides the default project in settings. Leave blank to use default.</p>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">{{ old('notes') }}</textarea>
            </div>

        </div>

        <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3 rounded-b-lg">
            <a href="{{ route('tsdm.deployments.index') }}"
               class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                Create Deployment
            </button>
        </div>
    </form>
</div>

@endsection
