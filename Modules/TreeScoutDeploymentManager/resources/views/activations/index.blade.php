@extends('tsdm::layouts.master')

@section('module-content')

<div class="flex justify-between items-center mb-6">
    <div>
        <nav class="text-xs text-gray-400 mb-1">
            <a href="{{ route('tsdm.dashboard') }}" class="hover:text-primary-600">Control Tower</a>
            <span class="mx-1">/</span>
            <span class="text-gray-600">Activations</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">Activation Codes</h1>
    </div>
</div>

{{-- ── NEW CODE REVEALED ───────────────────────────────────────────────── --}}
@if (session('new_code'))
<div class="mb-6 bg-green-50 border-2 border-green-400 rounded-lg p-5"
     x-data="{ copied: false }">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-bold text-green-800 mb-2">
                Activation code generated — copy it now. It will not be shown again.
            </p>
            <p class="font-mono text-2xl font-bold text-green-900 tracking-widest">
                {{ session('new_code') }}
            </p>
        </div>
        <button @click="navigator.clipboard.writeText('{{ session('new_code') }}'); copied = true"
                class="ml-4 px-3 py-1.5 text-xs font-medium border border-green-400 text-green-700 rounded-lg hover:bg-green-100">
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak>Copied ✓</span>
        </button>
    </div>
    <div class="mt-3 bg-green-100 rounded p-3 text-xs text-green-700 font-mono">
        # On the client server:<br>
        ./deploy.sh --code={{ session('new_code') }}
    </div>
</div>
@endif

{{-- ── ISSUE NEW CODE FORM ─────────────────────────────────────────────── --}}
@can('issue_tsdm_activations')
<div class="bg-white shadow-sm rounded-lg p-5 mb-8" x-data="{ open: false }">
    <button @click="open = !open"
            class="flex items-center justify-between w-full text-left">
        <span class="text-sm font-semibold text-gray-700">Issue New Activation Code</span>
        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-4 border-t border-gray-100 pt-4">
        <form method="POST" action="{{ route('tsdm.activations.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Deployment <span class="text-red-400">*</span></label>
                <select name="deployment_record_id" required
                        class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">— Select —</option>
                    @foreach ($deployments as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                <input type="text" name="label" placeholder="e.g. Initial install"
                       class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Expires in (hours)</label>
                <input type="number" name="ttl_hours" value="{{ config('tsdm.activation.ttl_hours', 24) }}"
                       min="1" max="168"
                       class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <button type="submit"
                        class="w-full px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
                    Generate
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ── ACTIVATIONS TABLE ───────────────────────────────────────────────── --}}
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deployment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Used At</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From IP</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($activations as $act)
            @php
                $stateLabel = $act->statusLabel();
                $sc = ['Used' => 'bg-gray-100 text-gray-600', 'Valid' => 'bg-green-100 text-green-700', 'Expired' => 'bg-red-100 text-red-700'];
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3 font-mono text-sm text-gray-900 whitespace-nowrap">
                    {{ $act->activation_code }}
                </td>
                <td class="px-6 py-3 text-sm text-gray-700">
                    @if ($act->deploymentRecord)
                    <a href="{{ route('tsdm.deployments.show', $act->deploymentRecord) }}"
                       class="hover:text-primary-600">
                        {{ $act->deploymentRecord->name }}
                    </a>
                    @else
                    <span class="text-gray-400">Deleted</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $act->label ?? '—' }}</td>
                <td class="px-6 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$stateLabel] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $stateLabel }}
                    </span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                    {{ $act->expires_at->format('Y-m-d H:i') }}
                </td>
                <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                    {{ $act->used_at?->format('Y-m-d H:i') ?? '—' }}
                </td>
                <td class="px-6 py-3 text-xs font-mono text-gray-400">
                    {{ $act->redeemed_from_ip ?? '—' }}
                </td>
                <td class="px-6 py-3 text-right">
                    @if ($act->isValid())
                    @can('issue_tsdm_activations')
                    <form method="POST" action="{{ route('tsdm.activations.expire', $act) }}"
                          onsubmit="return confirm('Expire this activation code?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">Expire</button>
                    </form>
                    @endcan
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">No activation codes found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($activations->hasPages())
    <div class="border-t border-gray-200 px-6 py-3">
        {{ $activations->links() }}
    </div>
    @endif
</div>

@endsection
