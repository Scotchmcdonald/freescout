<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Project') }}
            </h2>
            <a href="{{ route('milestones.index') }}" 
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                {{ __('Back to Milestones') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="mb-6 bg-danger-50 border-l-4 border-danger-500 p-4" dusk="validation-errors">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-danger-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-danger-800">
                                        {{ __('There were errors with your submission') }}
                                    </h3>
                                    <ul class="mt-2 list-disc list-inside text-sm text-danger-700">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('milestones.store') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Project Billing Fields (for Dusk tests) -->
                        <div class="space-y-6">
                            <div>
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Client') }} <span class="text-danger-600">*</span>
                                </label>
                                <select name="client_id" 
                                        id="client_id" 
                                        dusk="client-select"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">{{ __('Select Client...') }}</option>
                                    @foreach(\Modules\Crm\Models\Client::orderBy('name')->get() as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Project Name') }} <span class="text-danger-600">*</span>
                                </label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name') }}"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                       placeholder="e.g., Custom CRM Development">
                                @error('name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="total-value" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Total Value') }}
                                    </label>
                                    <input type="number" 
                                           name="total-value" 
                                           id="total-value" 
                                           step="0.01"
                                           value="{{ old('total-value') }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                           placeholder="30000.00">
                                </div>
                                
                                <div>
                                    <label for="billing-type" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Billing Type') }}
                                    </label>
                                    <select name="billing-type" 
                                            id="billing-type" 
                                            dusk="billing-type"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="fixed" {{ old('billing-type') == 'fixed' ? 'selected' : '' }}>Fixed Price</option>
                                        <option value="milestone" {{ old('billing-type', 'milestone') == 'milestone' ? 'selected' : '' }}>Milestone-Based</option>
                                        <option value="hourly" {{ old('billing-type') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="require_approval" dusk="require-milestone-approval" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">{{ __('Require Milestone Approval') }}</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Milestones Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Milestones') }}</h3>
                                <button type="button" 
                                        dusk="add-milestone-button"
                                        onclick="addMilestoneRow()"
                                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                    {{ __('Add Milestone') }}
                                </button>
                            </div>
                            
                            <div id="milestones-container" class="space-y-3">
                                <!-- Milestones will be added here dynamically -->
                            </div>
                        </div>
                        
                        <!-- Basic Information -->
                        <div class="space-y-6 border-t border-gray-200 pt-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Milestone Title') }}
                                </label>
                                <input type="text" 
                                       name="title" 
                                       id="title" 
                                       value="{{ old('title') }}"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                       placeholder="e.g., Planning & Discovery">
                                @error('title')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Description') }}
                                </label>
                                <textarea name="description" 
                                          id="description" 
                                          rows="3"
                                          class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                          placeholder="Describe the work involved in this milestone...">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Project Association -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Project Association') }}</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="project_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Project Type') }}
                                    </label>
                                    <select name="project_type" 
                                            id="project_type" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">{{ __('Select project type...') }}</option>
                                        <option value="email_migration" {{ old('project_type') == 'email_migration' ? 'selected' : '' }}>Email Migration</option>
                                        <option value="quote" {{ old('project_type') == 'quote' ? 'selected' : '' }}>Quote/Proposal</option>
                                        <option value="onboarding" {{ old('project_type') == 'onboarding' ? 'selected' : '' }}>Client Onboarding</option>
                                        <option value="project" {{ old('project_type') == 'project' ? 'selected' : '' }}>General Project</option>
                                    </select>
                                    @error('project_type')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Project ID') }}
                                    </label>
                                    <input type="number" 
                                           name="project_id" 
                                           id="project_id" 
                                           value="{{ old('project_id') }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                           placeholder="1">
                                    <p class="mt-1 text-xs text-gray-500">{{ __('ID of the associated project record') }}</p>
                                    @error('project_id')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status & Progress -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Status & Progress') }}</h3>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="sequence_order" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Sequence Order') }}
                                    </label>
                                    <input type="number" 
                                           name="sequence_order" 
                                           id="sequence_order" 
                                           min="1"
                                           value="{{ old('sequence_order', 1) }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Position in timeline') }}</p>
                                    @error('sequence_order')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Status') }}
                                    </label>
                                    <select name="status" 
                                            id="status" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="achieved" {{ old('status') == 'achieved' ? 'selected' : '' }}>Achieved</option>
                                        <option value="blocked" {{ old('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                        <option value="skipped" {{ old('status') == 'skipped' ? 'selected' : '' }}>Skipped</option>
                                    </select>
                                    @error('status')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="progress_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Progress %') }}
                                    </label>
                                    <input type="number" 
                                           name="progress_percentage" 
                                           id="progress_percentage" 
                                           min="0"
                                           max="100"
                                           step="0.01"
                                           value="{{ old('progress_percentage', 0) }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    @error('progress_percentage')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Timeline & Assignment -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Timeline & Assignment') }}</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="target_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Target Date') }}
                                    </label>
                                    <input type="date" 
                                           name="target_date" 
                                           id="target_date" 
                                           value="{{ old('target_date') }}"
                                           min="{{ date('Y-m-d') }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    @error('target_date')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Assign To') }}
                                    </label>
                                    <select name="assigned_to" 
                                            id="assigned_to" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">{{ __('Unassigned') }}</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Additional Details') }}</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Notes') }}
                                    </label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              rows="3"
                                              class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                              placeholder="Any additional context or requirements...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div x-data="{ showBlocker: {{ old('status') == 'blocked' ? 'true' : 'false' }} }">
                                    <div class="flex items-center mb-2">
                                        <input type="checkbox" 
                                               id="has_blocker" 
                                               x-model="showBlocker"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label for="has_blocker" class="ml-2 text-sm font-medium text-gray-700">
                                            {{ __('This milestone is blocked') }}
                                        </label>
                                    </div>
                                    
                                    <div x-show="showBlocker" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                                         x-transition:enter-end="opacity-100 transform translate-y-0">
                                        <textarea name="blockers" 
                                                  id="blockers" 
                                                  rows="2"
                                                  class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                  placeholder="Describe what is blocking progress...">{{ old('blockers') }}</textarea>
                                        @error('blockers')
                                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="border-t border-gray-200 pt-6 flex justify-between">
                            <a href="{{ route('milestones.index') }}" 
                               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    dusk="save-project-button"
                                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200">
                                {{ __('Save Project') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let milestoneCount = 0;
        
        function addMilestoneRow() {
            milestoneCount++;
            const container = document.getElementById('milestones-container');
            const div = document.createElement('div');
            div.className = 'p-4 border border-gray-200 rounded-lg';
            div.innerHTML = `
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <input type="text" 
                               name="milestone-name-${milestoneCount}"
                               dusk="milestone-name-${milestoneCount}"
                               class="w-full border-gray-300 rounded shadow-sm"
                               placeholder="Milestone name">
                    </div>
                    <div>
                        <input type="number" 
                               name="milestone-percentage-${milestoneCount}"
                               dusk="milestone-percentage-${milestoneCount}"
                               class="w-full border-gray-300 rounded shadow-sm"
                               placeholder="% of total"
                               step="1">
                    </div>
                    <div>
                        <input type="number" 
                               name="milestone-amount-${milestoneCount}"
                               dusk="milestone-amount-${milestoneCount}"
                               class="w-full border-gray-300 rounded shadow-sm"
                               placeholder="Amount"
                               step="0.01">
                    </div>
                </div>
            `;
            container.appendChild(div);
        }
    </script>
</x-app-layout>
