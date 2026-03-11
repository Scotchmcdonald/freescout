@extends('tsdm::layouts.master')

@section('module-content')

<nav class="text-xs text-gray-400 mb-1">
    <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
    <span class="mx-1">/</span>
    <a href="{{ route('tsdm.deployments.index') }}" class="hover:text-primary-600">Deployments</a>
    <span class="mx-1">/</span>
    <a href="{{ route('tsdm.deployments.show', $deployment) }}" class="hover:text-primary-600">{{ $deployment->name }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-600">Edit</span>
</nav>
<h1 class="text-2xl font-bold text-gray-900 mb-8">Edit Deployment</h1>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('tsdm.deployments.update', $deployment) }}"
          class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
        @csrf
        @method('PUT')

        <div class="px-6 py-5 space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deployment Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $deployment->name) }}" required
                       class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                    <select name="environment"
                            class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                        @foreach (['production', 'staging', 'development'] as $env)
                        <option value="{{ $env }}" {{ old('environment', $deployment->environment) === $env ? 'selected' : '' }}>
                            {{ ucfirst($env) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Git Provider</label>
                    <select name="git_provider"
                            class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                        @foreach (['gitlab' => 'GitLab', 'github' => 'GitHub'] as $val => $label)
                        <option value="{{ $val }}" {{ old('git_provider', $deployment->git_provider) === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Git Project ID</label>
                <input type="text" name="git_project_id"
                       value="{{ old('git_project_id', $deployment->git_project_id) }}"
                       class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">{{ old('notes', $deployment->notes) }}</textarea>
            </div>

        </div>

        <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3 rounded-b-lg">
            <a href="{{ route('tsdm.deployments.show', $deployment) }}"
               class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                Save Changes
            </button>
        </div>
    </form>
</div>

@endsection
