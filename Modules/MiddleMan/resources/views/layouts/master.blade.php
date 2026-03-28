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
                </select>
            </div>
            <div class="hidden sm:block">
                <div class="border-b border-neutral-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
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
