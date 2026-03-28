@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
        <!-- Module Header & Navigation -->
        <div class="mb-8">
            <div class="sm:hidden">
                <label for="tabs" class="sr-only">Select a section</label>
                <select id="tabs" name="tabs"
                    class="block w-full rounded-md border-neutral-300 focus:border-primary-500 focus:ring-primary-500"
                    onchange="window.location.href=this.value">
                    <option value="{{ route('middleman.dashboard') }}"
                        {{ request()->routeIs('middleman.dashboard') ? 'selected' : '' }}>Dashboard</option>
                    <option value="{{ route('middleman.logging.index') }}"
                        {{ request()->routeIs('middleman.logging.*') ? 'selected' : '' }}>Logging</option>
                    <option value="{{ route('middleman.intercept.index') }}"
                        {{ request()->routeIs('middleman.intercept.*') ? 'selected' : '' }}>Intercept</option>
                    <option value="{{ route('middleman.marshal.index') }}"
                        {{ request()->routeIs('middleman.marshal.*') ? 'selected' : '' }}>Marshal</option>
                    <option value="{{ route('middleman.topology.index') }}"
                        {{ request()->routeIs('middleman.topology.*') ? 'selected' : '' }}>Topology</option>
                    <option value="{{ route('middleman.schema.index') }}"
                        {{ request()->routeIs('middleman.schema.*') ? 'selected' : '' }}>Schema Drift</option>
                    <option value="{{ route('middleman.tracing.index') }}"
                        {{ request()->routeIs('middleman.tracing.*') ? 'selected' : '' }}>Tracing</option>
                    <option value="{{ route('middleman.replay.index') }}"
                        {{ request()->routeIs('middleman.replay.index') ? 'selected' : '' }}>Replay</option>
                    <option value="{{ route('middleman.muting.index') }}"
                        {{ request()->routeIs('middleman.muting.*') ? 'selected' : '' }}>Muting</option>
                </select>
            </div>
            <div class="hidden sm:block">
                <div class="border-b border-neutral-200">
                    <nav class="-mb-px flex flex-wrap gap-x-8" aria-label="Tabs">
                        {{-- Dashboard --}}
                        <a href="{{ route('middleman.dashboard') }}"
                            class="{{ request()->routeIs('middleman.dashboard') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.dashboard') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        {{-- Logging --}}
                        <a href="{{ route('middleman.logging.index') }}"
                            class="{{ request()->routeIs('middleman.logging.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.logging.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Logging</span>
                        </a>

                        {{-- Intercept --}}
                        <a href="{{ route('middleman.intercept.index') }}"
                            class="{{ request()->routeIs('middleman.intercept.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.intercept.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Intercept</span>
                        </a>

                        {{-- Marshal --}}
                        <a href="{{ route('middleman.marshal.index') }}"
                            class="{{ request()->routeIs('middleman.marshal.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.marshal.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span>Marshal</span>
                        </a>

                        {{-- Topology --}}
                        <a href="{{ route('middleman.topology.index') }}"
                            class="{{ request()->routeIs('middleman.topology.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.topology.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6h7M4 12h16M4 18h7M15 6h5M15 18h5" />
                            </svg>
                            <span>Topology</span>
                        </a>

                        {{-- Schema Drift --}}
                        <a href="{{ route('middleman.schema.index') }}"
                            class="{{ request()->routeIs('middleman.schema.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.schema.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                            </svg>
                            <span>Schema</span>
                        </a>

                        {{-- Tracing --}}
                        <a href="{{ route('middleman.tracing.index') }}"
                            class="{{ request()->routeIs('middleman.tracing.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.tracing.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h3l3 8 4-16 3 8h5" />
                            </svg>
                            <span>Tracing</span>
                        </a>

                        {{-- Replay --}}
                        <a href="{{ route('middleman.replay.index') }}"
                            class="{{ request()->routeIs('middleman.replay.index') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.replay.index') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h4l3-3m0 0l3 3m-3-3v10m5-5h6" />
                            </svg>
                            <span>Replay</span>
                        </a>

                        {{-- Muting --}}
                        <a href="{{ route('middleman.muting.index') }}"
                            class="{{ request()->routeIs('middleman.muting.*') ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium">
                            <svg class="{{ request()->routeIs('middleman.muting.*') ? 'text-primary-500' : 'text-neutral-400 group-hover:text-neutral-500' }} -ml-0.5 mr-2 h-5 w-5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5L6 9H3v6h3l5 4V5zM19 9l-6 6" />
                            </svg>
                            <span>Muting</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="w-full">
            @yield('module-content')
        </div>
    </div>
@endsection
