@extends('layouts.app')

@section('title', 'Project Milestones')

@section('content')
<div class="py-6">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Project Milestones
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Track progress through project phases with visual stepper
                </p>
            </div>
            <a href="{{ route('milestones.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Milestone
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Total Milestones -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                <div class="text-sm font-medium text-gray-600">Total</div>
                <div class="mt-2 text-3xl font-bold text-gray-800">{{ $stats['total'] }}</div>
            </div>

            <!-- Achieved -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <div class="text-sm font-medium text-gray-600">Achieved</div>
                <div class="mt-2 text-3xl font-bold text-green-600">{{ $stats['achieved'] }}</div>
            </div>

            <!-- In Progress -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                <div class="text-sm font-medium text-gray-600">In Progress</div>
                <div class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['in_progress'] }}</div>
            </div>

            <!-- Blocked -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                <div class="text-sm font-medium text-gray-600">Blocked</div>
                <div class="mt-2 text-3xl font-bold text-red-600">{{ $stats['blocked'] }}</div>
            </div>

            <!-- Overdue -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                <div class="text-sm font-medium text-gray-600">Overdue</div>
                <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $stats['overdue'] }}</div>
            </div>
        </div>
    </div>

    <!-- Overall Progress Bar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-medium text-gray-900">Overall Progress</h3>
                <span class="text-2xl font-bold text-indigo-600">{{ $stats['overall_progress'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-indigo-600 h-4 rounded-full transition-all duration-500" 
                     style="width: {{ $stats['overall_progress'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Milestone Stepper -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($milestones->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No milestones yet</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Create your first project milestone to start tracking progress
                </p>
                <a href="{{ route('milestones.create') }}" 
                   class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    Add Milestone
                </a>
            </div>
        @else
            <!-- Vertical Stepper -->
            <div class="bg-white shadow-sm sm:rounded-lg p-8">
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($milestones as $milestone)
                            @php
                                $statusInfo = $milestone->getStatusInfo();
                                $isLastItem = $loop->last;
                            @endphp
                            
                            <li x-data="{ 
                                showDetails: false,
                                updating: false,
                                progress: {{ $milestone->progress_percentage }}
                            }">
                                <div class="relative pb-8">
                                    @if(!$isLastItem)
                                        <!-- Connecting line -->
                                        <span class="absolute top-10 left-6 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    
                                    <div class="relative flex items-start space-x-4">
                                        <!-- Status Icon with Pulse Animation -->
                                        <div class="relative flex-shrink-0">
                                            <span class="h-12 w-12 rounded-full {{ $statusInfo['ring'] }} flex items-center justify-center ring-8 ring-white
                                                {{ $milestone->status === 'in_progress' ? 'animate-pulse' : '' }}">
                                                @if($milestone->status === 'achieved')
                                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                @elseif($milestone->status === 'blocked')
                                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                @elseif($milestone->status === 'in_progress')
                                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                @else
                                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Milestone Content -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        {{ $milestone->title }}
                                                    </h3>
                                                    @if($milestone->description)
                                                        <p class="mt-1 text-sm text-gray-600">{{ $milestone->description }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-3 ml-4">
                                                    <!-- Status Badge -->
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusInfo['color'] }}">
                                                        {{ $statusInfo['label'] }}
                                                    </span>
                                                    
                                                    <!-- Progress Percentage -->
                                                    <span class="text-lg font-bold text-gray-900">{{ number_format($milestone->progress_percentage, 0) }}%</span>
                                                    
                                                    <!-- Toggle Details Button -->
                                                    <button @click="showDetails = !showDetails" 
                                                            class="text-gray-400 hover:text-gray-600 transition">
                                                        <svg class="w-5 h-5 transform transition-transform" 
                                                             :class="{ 'rotate-180': showDetails }"
                                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                                                <div class="{{ $statusInfo['ring'] }} h-2 rounded-full transition-all duration-500" 
                                                     :style="`width: ${progress}%`"></div>
                                            </div>

                                            <!-- Expandable Details -->
                                            <div x-show="showDetails" 
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                                 class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                    <!-- Timeline Info -->
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Timeline</h4>
                                                        <dl class="space-y-1 text-sm">
                                                            @if($milestone->target_date)
                                                                <div class="flex justify-between">
                                                                    <dt class="text-gray-600">Target Date:</dt>
                                                                    <dd class="font-medium text-gray-900">
                                                                        {{ $milestone->target_date->format('M j, Y') }}
                                                                        @if($milestone->isOverdue())
                                                                            <span class="ml-2 text-xs text-red-600 font-semibold">(Overdue)</span>
                                                                        @endif
                                                                    </dd>
                                                                </div>
                                                            @endif
                                                            @if($milestone->started_at)
                                                                <div class="flex justify-between">
                                                                    <dt class="text-gray-600">Started:</dt>
                                                                    <dd class="font-medium text-gray-900">{{ $milestone->started_at->format('M j, Y') }}</dd>
                                                                </div>
                                                            @endif
                                                            @if($milestone->completed_at)
                                                                <div class="flex justify-between">
                                                                    <dt class="text-gray-600">Completed:</dt>
                                                                    <dd class="font-medium text-gray-900">{{ $milestone->completed_at->format('M j, Y') }}</dd>
                                                                </div>
                                                            @endif
                                                            @if($milestone->duration)
                                                                <div class="flex justify-between">
                                                                    <dt class="text-gray-600">Duration:</dt>
                                                                    <dd class="font-medium text-gray-900">{{ $milestone->duration }}</dd>
                                                                </div>
                                                            @endif
                                                        </dl>
                                                    </div>

                                                    <!-- Assignment Info -->
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Assignment</h4>
                                                        @if($milestone->assignedUser)
                                                            <div class="flex items-center space-x-2">
                                                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                                    <span class="text-sm font-medium text-indigo-600">
                                                                        {{ substr($milestone->assignedUser->name, 0, 2) }}
                                                                    </span>
                                                                </div>
                                                                <span class="text-sm text-gray-900">{{ $milestone->assignedUser->name }}</span>
                                                            </div>
                                                        @else
                                                            <p class="text-sm text-gray-500 italic">Not assigned</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Blockers Alert -->
                                                @if($milestone->blockers)
                                                    <div class="bg-red-50 border-l-4 border-red-500 p-3 mb-4">
                                                        <div class="flex">
                                                            <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                            </svg>
                                                            <div>
                                                                <p class="text-sm font-medium text-red-800">Blockers</p>
                                                                <p class="text-sm text-red-700 mt-1">{{ $milestone->blockers }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Notes -->
                                                @if($milestone->notes)
                                                    <div class="mb-4">
                                                        <h4 class="text-sm font-medium text-gray-900 mb-1">Notes</h4>
                                                        <p class="text-sm text-gray-600">{{ $milestone->notes }}</p>
                                                    </div>
                                                @endif

                                                <!-- Action Buttons -->
                                                <div class="flex space-x-3">
                                                    <a href="{{ route('milestones.edit', $milestone) }}" 
                                                       class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    
                                                    @if(!$milestone->isAchieved())
                                                        <button @click="
                                                            if (confirm('Mark this milestone as achieved?')) {
                                                                updating = true;
                                                                fetch('{{ route('milestones.updateStatus', $milestone) }}', {
                                                                    method: 'POST',
                                                                    headers: {
                                                                        'Content-Type': 'application/json',
                                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                    },
                                                                    body: JSON.stringify({ status: 'achieved' })
                                                                })
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    if (data.success) {
                                                                        window.location.reload();
                                                                    }
                                                                })
                                                                .finally(() => updating = false);
                                                            }
                                                        "
                                                        :disabled="updating"
                                                        class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700 transition disabled:opacity-50">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            <span x-show="!updating">Mark Complete</span>
                                                            <span x-show="updating">Updating...</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
