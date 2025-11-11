<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Access Forbidden | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-2">403</h1>
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Access Forbidden</h2>
            
            <p class="text-gray-600 mb-6">
                You don't have permission to access this resource. 
                If you believe this is an error, please contact your administrator.
            </p>
            
            <div class="space-x-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Go to Dashboard
                    </a>
                    
                    @if(!auth()->user()->isAdmin())
                    <a href="mailto:{{ config('mail.from.address') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Contact Admin
                    </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Go to Login
                    </a>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
