<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Sync Operation #{{ $operation->id }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $operation->source }} - {{ $operation->operation_type }}
                </p>
            </div>
            <div class="flex gap-2">
                @if(in_array($operation->status, ['stalled', 'paused']))
                    <form method="POST" action="{{ route('admin.sync-monitor.resume', $operation) }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="success">
                            Resume Operation
                        </x-button>
                    </form>
                @endif

                @if($operation->status === 'failed')
                    <form method="POST" action="{{ route('admin.sync-monitor.retry', $operation) }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="warning">
                            Retry Operation
                        </x-button>
                    </form>
                @endif

                <a href="{{ route('admin.sync-monitor.index') }}">
                    <x-button variant="secondary">Back to List</x-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <x-alert variant="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert variant="danger">
                    {{ session('error') }}
                </x-alert>
            @endif

            <!-- Status Overview -->
            <x-card>
                <h3 class="text-lg font-semibold mb-4">Operation Status</h3>
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-600">Status</div>
                        <div class="mt-1">
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
                            <x-badge :variant="$statusVariants[$operation->status] ?? 'secondary'" class="text-base">
                                {{ ucfirst($operation->status) }}
                            </x-badge>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Progress</div>
                        <div class="mt-1 font-semibold text-lg">
                            {{ $operation->progress_percentage }}%
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Items Processed</div>
                        <div class="mt-1 font-semibold text-lg">
                            {{ number_format($operation->processed_items) }} / {{ number_format($operation->total_items) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Speed</div>
                        <div class="mt-1 font-semibold text-lg font-mono">
                            @if($operation->items_per_second > 0)
                                {{ number_format($operation->items_per_second, 1) }}/s
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-6">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-blue-600 h-4 rounded-full transition-all flex items-center justify-end pr-2"
                             style="width: {{ $operation->progress_percentage }}%">
                            <span class="text-xs text-white font-medium">{{ $operation->progress_percentage }}%</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t">
                    <div>
                        <div class="text-sm text-gray-600">Success</div>
                        <div class="mt-1 text-lg font-semibold text-green-600">
                            {{ number_format($operation->success_items) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Failed</div>
                        <div class="mt-1 text-lg font-semibold text-red-600">
                            {{ number_format($operation->failed_items) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Remaining</div>
                        <div class="mt-1 text-lg font-semibold text-gray-600">
                            {{ number_format($operation->total_items - $operation->processed_items) }}
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Timing Information -->
            <x-card>
                <h3 class="text-lg font-semibold mb-4">Timing</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-gray-600">Started At</div>
                        <div class="mt-1 font-mono text-sm">
                            {{ $operation->started_at->format('Y-m-d H:i:s') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $operation->started_at->diffForHumans() }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Last Progress</div>
                        <div class="mt-1 font-mono text-sm">
                            @if($operation->last_progress_at)
                                {{ $operation->last_progress_at->format('Y-m-d H:i:s') }}
                                <div class="text-xs text-gray-500">
                                    {{ $operation->last_progress_at->diffForHumans() }}
                                </div>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Completed At</div>
                        <div class="mt-1 font-mono text-sm">
                            @if($operation->completed_at)
                                {{ $operation->completed_at->format('Y-m-d H:i:s') }}
                                <div class="text-xs text-gray-500">
                                    Duration: {{ $operation->started_at->diff($operation->completed_at)->format('%H:%I:%S') }}
                                </div>
                            @else
                                <span class="text-gray-400">In Progress</span>
                                @if($operation->estimated_time_remaining)
                                    <div class="text-xs text-gray-500">
                                        ~{{ $operation->estimated_time_remaining }} remaining
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Error Message -->
            @if($operation->error_message)
                <x-card>
                    <h3 class="text-lg font-semibold mb-4 text-red-600">Error Details</h3>
                    <x-alert variant="danger">
                        {{ $operation->error_message }}
                    </x-alert>
                </x-card>
            @endif

            <!-- Failures List -->
            @if($operation->failures && count($operation->failures) > 0)
                <x-card>
                    <h3 class="text-lg font-semibold mb-4">Failed Items ({{ count($operation->failures) }})</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failed At</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(collect($operation->failures)->take(50) as $failure)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono">
                                            {{ $failure['item'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-red-600">
                                            {{ $failure['reason'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ \Carbon\Carbon::parse($failure['failed_at'])->format('H:i:s') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if(count($operation->failures) > 50)
                            <div class="px-4 py-3 text-sm text-gray-600 bg-gray-50 border-t">
                                Showing first 50 failures. Total: {{ count($operation->failures) }}
                            </div>
                        @endif
                    </div>
                </x-card>
            @endif

            <!-- Checkpoint Data -->
            @if($operation->checkpoint_data)
                <x-card>
                    <h3 class="text-lg font-semibold mb-4">Checkpoint Data</h3>
                    <pre class="bg-gray-100 p-4 rounded text-xs font-mono overflow-x-auto">{{ json_encode($operation->checkpoint_data, JSON_PRETTY_PRINT) }}</pre>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
