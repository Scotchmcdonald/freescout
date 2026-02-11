<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Milestone') }}: {{ $milestone->title }}
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
                        <div class="mb-6 bg-danger-50 border-l-4 border-danger-500 p-4">
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
                    
                    <form method="POST" action="{{ route('milestones.update', $milestone) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Title') }} <span class="text-danger-600">*</span>
                                </label>
                                <input type="text" 
                                       name="title" 
                                       id="title" 
                                       required
                                       value="{{ old('title', $milestone->title) }}"
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
                                          placeholder="Describe the work involved in this milestone...">{{ old('description', $milestone->description) }}</textarea>
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
                                        {{ __('Project Type') }} <span class="text-danger-600">*</span>
                                    </label>
                                    <select name="project_type" 
                                            id="project_type" 
                                            required
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">{{ __('Select project type...') }}</option>
                                        <option value="email_migration" {{ old('project_type', $milestone->project_type) == 'email_migration' ? 'selected' : '' }}>Email Migration</option>
                                        <option value="quote" {{ old('project_type', $milestone->project_type) == 'quote' ? 'selected' : '' }}>Quote/Proposal</option>
                                        <option value="onboarding" {{ old('project_type', $milestone->project_type) == 'onboarding' ? 'selected' : '' }}>Client Onboarding</option>
                                        <option value="project" {{ old('project_type', $milestone->project_type) == 'project' ? 'selected' : '' }}>General Project</option>
                                    </select>
                                    @error('project_type')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Project ID') }} <span class="text-danger-600">*</span>
                                    </label>
                                    <input type="number" 
                                           name="project_id" 
                                           id="project_id" 
                                           required
                                           value="{{ old('project_id', $milestone->project_id) }}"
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
                                        {{ __('Sequence Order') }} <span class="text-danger-600">*</span>
                                    </label>
                                    <input type="number" 
                                           name="sequence_order" 
                                           id="sequence_order" 
                                           required
                                           min="1"
                                           value="{{ old('sequence_order', $milestone->sequence_order) }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Position in timeline') }}</p>
                                    @error('sequence_order')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Status') }} <span class="text-danger-600">*</span>
                                    </label>
                                    <select name="status" 
                                            id="status" 
                                            required
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="pending" {{ old('status', $milestone->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="in_progress" {{ old('status', $milestone->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="achieved" {{ old('status', $milestone->status) == 'achieved' ? 'selected' : '' }}>Achieved</option>
                                        <option value="blocked" {{ old('status', $milestone->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                        <option value="skipped" {{ old('status', $milestone->status) == 'skipped' ? 'selected' : '' }}>Skipped</option>
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
                                           value="{{ old('progress_percentage', $milestone->progress_percentage) }}"
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
                                           value="{{ old('target_date', $milestone->target_date ? $milestone->target_date->format('Y-m-d') : '') }}"
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
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $milestone->assigned_to) == $user->id ? 'selected' : '' }}>
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
                                              placeholder="Any additional context or requirements...">{{ old('notes', $milestone->notes) }}</textarea>
                                    @error('notes')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div x-data="{ showBlocker: {{ old('blockers', $milestone->blockers) ? 'true' : 'false' }} }">
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
                                                  placeholder="Describe what is blocking progress...">{{ old('blockers', $milestone->blockers) }}</textarea>
                                        @error('blockers')
                                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamp Information -->
                        @if($milestone->started_at || $milestone->completed_at)
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Timeline History') }}</h3>
                            
                            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                @if($milestone->started_at)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ __('Started:') }}</span>
                                        <span class="text-gray-900 font-medium">{{ $milestone->started_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                @endif
                                
                                @if($milestone->completed_at)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ __('Completed:') }}</span>
                                        <span class="text-gray-900 font-medium">{{ $milestone->completed_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                @endif
                                
                                @if($milestone->started_at && $milestone->completed_at)
                                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                        <span class="text-gray-600">{{ __('Duration:') }}</span>
                                        <span class="text-gray-900 font-medium">{{ $milestone->duration }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="border-t border-gray-200 pt-6 flex justify-between">
                            <a href="{{ route('milestones.index') }}" 
                               class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                {{ __('Cancel') }}
                            </a>
                            <div class="flex gap-3">
                                <button type="submit" 
                                        class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200">
                                    {{ __('Update Milestone') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="flex justify-end mt-4 px-6 pb-6">
                        <form method="POST" action="{{ route('milestones.destroy', $milestone) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this milestone?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="px-6 py-2 border border-danger-600 text-danger-600 rounded-lg hover:bg-danger-50 transition-colors duration-200">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
