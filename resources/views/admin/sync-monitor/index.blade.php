<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Sync Operation Monitor') }}
            </h2>
            <div class="flex gap-2">
                <x-badge :variant="$stats['active'] > 0 ? 'warning' : 'success'">
                    {{ $stats['active'] }} Active
                </x-badge>
                <x-badge variant="success">{{ $stats['completed_24h'] }} Completed (24h)</x-badge>
                @if($stats['failed_24h'] > 0)
                    <x-badge variant="danger">{{ $stats['failed_24h'] }} Failed (24h)</x-badge>
                @endif
                @if($stats['stalled'] > 0)
                    <x-badge variant="warning">{{ $stats['stalled'] }} Stalled</x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert variant="success" class="mb-4">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert variant="danger" class="mb-4">
                    {{ session('error') }}
                </x-alert>
            @endif

            <!-- Filters -->
            <x-card class="mb-6">
                <form method="GET" action="{{ route('admin.sync-monitor.index') }}" class="flex gap-4">
                    <div class="flex-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="stalled" {{ request('status') === 'stalled' ? 'selected' : '' }}>Stalled</option>
                            <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label for="source" class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                        <select name="source" id="source" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">All Sources</option>
                            @foreach($sources as $source)
                                <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>
                                    {{ $source }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1">
                        <label for="hours" class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
                        <select name="hours" id="hours" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="1" {{ request('hours', 24) == 1 ? 'selected' : '' }}>Last Hour</option>
                            <option value="6" {{ request('hours', 24) == 6 ? 'selected' : '' }}>Last 6 Hours</option>
                            <option value="24" {{ request('hours', 24) == 24 ? 'selected' : '' }}>Last 24 Hours</option>
                            <option value="72" {{ request('hours', 24) == 72 ? 'selected' : '' }}>Last 3 Days</option>
                            <option value="168" {{ request('hours', 24) == 168 ? 'selected' : '' }}>Last Week</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <x-button type="submit">Filter</x-button>
                    </div>
                </form>
            </x-card>

            <!-- Operations Table -->
            <x-card>
                <x-data-table>
                    <x-slot name="header">
                        <x-data-table-header>ID</x-data-table-header>
                        <x-data-table-header>Source</x-data-table-header>
                        <x-data-table-header>Operation</x-data-table-header>
                        <x-data-table-header>Status</x-data-table-header>
                        <x-data-table-header>Progress</x-data-table-header>
                        <x-data-table-header>Speed</x-data-table-header>
                        <x-data-table-header>Started</x-data-table-header>
                        <x-data-table-header>Actions</x-data-table-header>
                    </x-slot>

                    @forelse($operations as $operation)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.sync-monitor.show', $operation) }}" class="text-primary-600 hover:text-primary-800 font-mono">
                                    #{{ $operation->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="font-medium">{{ $operation->source }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $operation->operation_type }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $statusVariants = [
                                        'running' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'stalled' => 'warning',
                                        'paused' => 'secondary',
                                        'cancelled' => 'secondary',
                                    ];
                                @endphp
                                <x-badge :variant="$statusVariants[$operation->status] ?? 'secondary'">
                                    {{ ucfirst($operation->status) }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="w-full">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span>{{ $operation->processed_items }} / {{ $operation->total_items }}</span>
                                        <span class="font-medium">{{ $operation->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-primary-600 h-2 rounded-full transition-all"
                                             style="width: {{ $operation->progress_percentage }}%"></div>
                                    </div>
                                    @if($operation->failed_items > 0)
                                        <div class="text-xs text-red-600 mt-1">
                                            {{ $operation->failed_items }} failures
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono">
                                @if($operation->items_per_second > 0)
                                    {{ number_format($operation->items_per_second, 1) }}/s
                                    @if($operation->estimated_time_remaining)
                                        <div class="text-xs text-gray-500">
                                            ~{{ $operation->estimated_time_remaining }} remaining
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $operation->started_at->diffForHumans() }}
                                <div class="text-xs text-gray-400">
                                    {{ $operation->started_at->format('H:i:s') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2">
                                    @if(in_array($operation->status, ['stalled', 'paused']))
                                        <form method="POST" action="{{ route('admin.sync-monitor.resume', $operation) }}" class="inline">
                                            @csrf
                                            <x-button type="submit" size="sm" variant="success">
                                                Resume
                                            </x-button>
                                        </form>
                                    @endif

                                    @if($operation->status === 'failed')
                                        <form method="POST" action="{{ route('admin.sync-monitor.retry', $operation) }}" class="inline">
                                            @csrf
                                            <x-button type="submit" size="sm" variant="warning">
                                                Retry
                                            </x-button>
                                        </form>
                                    @endif

                                    @if($operation->status === 'running')
                                        <form method="POST" action="{{ route('admin.sync-monitor.cancel', $operation) }}" class="inline">
                                            @csrf
                                            <x-button type="submit" size="sm" variant="danger"
                                                      onclick="return confirm('Cancel this sync operation?')">
                                                Cancel
                                            </x-button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.sync-monitor.show', $operation) }}">
                                        <x-button size="sm" variant="secondary">Details</x-button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No sync operations found in the selected time range.
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>

                @if($operations->hasPages())
                    <div class="mt-4">
                        {{ $operations->links() }}
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
