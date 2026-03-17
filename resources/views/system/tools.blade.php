@extends('layouts.app')

@section('title', __('System Tools'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white mb-6">{{ __('System Tools') }}</h1>

        @if(session('success'))
            <div class="bg-success-100 border border-success-400 text-success-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow p-6">
            <form method="POST" action="{{ route('system.tools.execute') }}" class="space-y-6">
                @csrf

                <!-- Main Tools -->
                <div class="border-b border-neutral-200 dark:border-neutral-700 pb-6">
                    <h2 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">{{ __('Maintenance') }}</h2>
                    <div class="flex flex-wrap gap-4">
                        <button type="submit" name="action" value="clear_cache" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            {{ __('Clear Cache') }}
                        </button>
                        <button type="submit" name="action" value="migrate_db" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            {{ __('Migrate DB') }}
                        </button>
                        <button type="submit" name="action" value="optimize" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            {{ __('Optimize') }}
                        </button>
                    </div>
                </div>

                <!-- Email Fetch Tool -->
                <div class="pt-2">
                    <h2 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">{{ __('Email Fetching') }}</h2>
                    <div class="flex flex-wrap items-end gap-4">
                        <button type="submit" name="action" value="fetch_emails" 
                            class="px-4 py-2 bg-success-600 text-white rounded-md hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-success-500">
                            {{ __('Fetch Emails') }}
                        </button>
                        <div>
                            <label for="days" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                {{ __('Days') }}
                            </label>
                            <input type="number" name="days" id="days" value="{{ old('days', 3) }}" 
                                class="w-20 px-3 py-2 border border-neutral-300 dark:border-neutral-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-neutral-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </form>

            <!-- Output Display -->
            @if($output ?? null)
                <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                    <h2 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">{{ __('Output') }}</h2>
                    <pre class="bg-neutral-100 dark:bg-neutral-900 p-4 rounded-lg overflow-x-auto text-sm text-neutral-800 dark:text-neutral-200 font-mono whitespace-pre-wrap">{{ $output }}</pre>
                </div>
            @endif
        </div>

        <!-- Web Cron Information -->
        <div class="mt-6 bg-white dark:bg-neutral-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">{{ __('Web Cron') }}</h2>
            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                {{ __('If you cannot set up a cron job on your server, you can use web cron services to trigger the following URL periodically:') }}
            </p>
            <div class="bg-neutral-100 dark:bg-neutral-900 p-3 rounded-lg">
                <code class="text-sm text-neutral-800 dark:text-neutral-200 break-all">
                    {{ route('system.cron', ['hash' => $cronHash]) }}
                </code>
            </div>
        </div>
    </div>
</div>
@endsection
