@extends('layouts.app')

@section('title', __('System Tools'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('System Tools') }}</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form method="POST" action="{{ route('system.tools.execute') }}" class="space-y-6">
                @csrf

                <!-- Main Tools -->
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Maintenance') }}</h2>
                    <div class="flex flex-wrap gap-4">
                        <button type="submit" name="action" value="clear_cache" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            {{ __('Clear Cache') }}
                        </button>
                        <button type="submit" name="action" value="migrate_db" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            {{ __('Migrate DB') }}
                        </button>
                        <button type="submit" name="action" value="optimize" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            {{ __('Optimize') }}
                        </button>
                    </div>
                </div>

                <!-- Email Fetch Tool -->
                <div class="pt-2">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Email Fetching') }}</h2>
                    <div class="flex flex-wrap items-end gap-4">
                        <button type="submit" name="action" value="fetch_emails" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            {{ __('Fetch Emails') }}
                        </button>
                        <div>
                            <label for="days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Days') }}
                            </label>
                            <input type="number" name="days" id="days" value="{{ old('days', 3) }}" 
                                class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </form>

            <!-- Output Display -->
            @if($output ?? null)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Output') }}</h2>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-sm text-gray-800 dark:text-gray-200 font-mono whitespace-pre-wrap">{{ $output }}</pre>
                </div>
            @endif
        </div>

        <!-- Web Cron Information -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Web Cron') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('If you cannot set up a cron job on your server, you can use web cron services to trigger the following URL periodically:') }}
            </p>
            <div class="bg-gray-100 dark:bg-gray-900 p-3 rounded-lg">
                <code class="text-sm text-gray-800 dark:text-gray-200 break-all">
                    {{ route('system.cron', ['hash' => \App\Http\Controllers\SystemController::getWebCronHash()]) }}
                </code>
            </div>
        </div>
    </div>
</div>
@endsection
