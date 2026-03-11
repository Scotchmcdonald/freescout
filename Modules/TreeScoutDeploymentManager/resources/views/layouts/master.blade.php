@extends('layouts.app')

@section('content')
{{-- x-cloak: hides Alpine.js elements until Alpine initialises (prevents flash) --}}
<style>[x-cloak] { display: none !important; }</style>
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">

    {{-- Module Top Navigation --}}
    <div class="mb-8">
        {{-- Mobile dropdown --}}
        <div class="sm:hidden">
            <select class="block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    onchange="window.location.href=this.value">
                <option value="{{ route('tsdm.dashboard') }}"       {{ request()->routeIs('tsdm.dashboard') ? 'selected' : '' }}>Control Tower</option>
                <option value="{{ route('tsdm.deployments.index') }}" {{ request()->routeIs('tsdm.deployments.*') ? 'selected' : '' }}>Deployments</option>
                <option value="{{ route('tsdm.activations.index') }}" {{ request()->routeIs('tsdm.activations.*') ? 'selected' : '' }}>Activations</option>
                <option value="{{ route('tsdm.settings.index') }}"   {{ request()->routeIs('tsdm.settings.*') ? 'selected' : '' }}>Settings</option>
            </select>
        </div>

        {{-- Desktop tab bar --}}
        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="TSDM Navigation">

                    @php
                        $navItems = [
                            ['label' => 'Control Tower',  'route' => 'tsdm.dashboard',          'match' => 'tsdm.dashboard',        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['label' => 'Deployments',    'route' => 'tsdm.deployments.index',  'match' => 'tsdm.deployments.*',    'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-6-4h.01M17 16h.01'],
                            ['label' => 'Activations',   'route' => 'tsdm.activations.index',  'match' => 'tsdm.activations.*',    'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                            ['label' => 'Settings',      'route' => 'tsdm.settings.index',     'match' => 'tsdm.settings.*',       'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="{{ $active ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ $active ? 'text-primary-500' : 'text-gray-400 group-hover:text-gray-500' }} -ml-0.5 mr-2 h-5 w-5"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 border border-green-200 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('info'))
        <div class="mb-6 rounded-md bg-blue-50 border border-blue-200 p-4">
            <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4">
            <ul class="list-disc pl-4 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main content slot --}}
    <div class="w-full">
        @yield('module-content')
    </div>
</div>
@endsection
