@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{ submittingId: null }" class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Replay Workspace</h2>
            <span
                class="inline-flex items-center rounded-full bg-success-100 px-3 py-1 text-xs font-medium text-success-800">
                {{ number_format($replayCount) }} Replays Recorded
            </span>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                <h3 class="text-sm font-semibold text-neutral-800">Recent Events Eligible for Replay</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Class</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 bg-white">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-3 text-xs text-neutral-600">
                                    {{ optional($log->fired_at)->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-neutral-800">{{ $log->event_name }}</td>
                                <td class="px-4 py-3 text-xs font-mono text-neutral-600">{{ $log->event_class }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('manage_middleman')
                                        <form method="POST" action="{{ route('middleman.replay', $log->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" @click="submittingId = {{ $log->id }}"
                                                :disabled="submittingId === {{ $log->id }}"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50">
                                                Replay
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-neutral-400">Read-only</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-sm text-neutral-500 italic">No events
                                    available to replay yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
