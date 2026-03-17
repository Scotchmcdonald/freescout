{{-- User Sidebar Menu Component --}}
{{-- Usage: <x-user-sidebar-menu :user="$user" :users="$users ?? collect()" /> --}}

@props(['user', 'users' => collect()])

<div class="mb-6">
    <h3 class="text-lg font-semibold text-neutral-900 px-4 mb-2">
        {{ $user->getFullName() }}
    </h3>
    
    @if($users->count() > 1)
        <div class="px-4 mb-4">
            <select onchange="if(this.value) window.location.href=this.value" 
                    class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($users as $userItem)
                    <option value="{{ route('users.show', $userItem) }}" 
                            @if($userItem->id == $user->id) selected @endif>
                        {{ $userItem->getFullName() }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</div>

<nav class="space-y-1 px-2">
    <a href="{{ route('users.show', $user) }}" 
       class="@if(Route::currentRouteName() == 'users.show') border-l-4 @else text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-transparent border-l-4 @endif group flex items-center px-3 py-2 text-sm font-medium transition"
       style="@if(Route::currentRouteName() == 'users.show') background-color: var(--theme-primary-50); color: var(--theme-primary-700); border-color: var(--theme-primary-700); @endif">
        <svg class="@if(Route::currentRouteName() == 'users.show') @else text-neutral-400 group-hover:text-neutral-500 @endif mr-3 h-5 w-5 flex-shrink-0" 
             style="@if(Route::currentRouteName() == 'users.show') color: var(--theme-primary-500); @endif"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        {{ __('Profile') }}
    </a>
    
    @if(Auth::check() && Auth::user()->isAdmin())
        <a href="{{ route('users.permissions', $user) }}" 
           class="@if(Route::currentRouteName() == 'users.permissions') border-l-4 @else text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-transparent border-l-4 @endif group flex items-center px-3 py-2 text-sm font-medium transition"
           style="@if(Route::currentRouteName() == 'users.permissions') background-color: var(--theme-primary-50); color: var(--theme-primary-700); border-color: var(--theme-primary-700); @endif">
            <svg class="@if(Route::currentRouteName() == 'users.permissions') @else text-neutral-400 group-hover:text-neutral-500 @endif mr-3 h-5 w-5 flex-shrink-0" 
                 style="@if(Route::currentRouteName() == 'users.permissions') color: var(--theme-primary-500); @endif"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            {{ __('Permissions') }}
        </a>
    @endif
    
    <a href="{{ route('users.notifications', $user) }}" 
       class="@if(Route::currentRouteName() == 'users.notifications') border-l-4 @else text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-transparent border-l-4 @endif group flex items-center px-3 py-2 text-sm font-medium transition"
       style="@if(Route::currentRouteName() == 'users.notifications') background-color: var(--theme-primary-50); color: var(--theme-primary-700); border-color: var(--theme-primary-700); @endif">
        <svg class="@if(Route::currentRouteName() == 'users.notifications') @else text-neutral-400 group-hover:text-neutral-500 @endif mr-3 h-5 w-5 flex-shrink-0" 
             style="@if(Route::currentRouteName() == 'users.notifications') color: var(--theme-primary-500); @endif"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        {{ __('Notifications') }}
    </a>
    
    <a href="{{ route('users.password', $user) }}" 
       class="@if(Route::currentRouteName() == 'users.password') border-l-4 @else text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-transparent border-l-4 @endif group flex items-center px-3 py-2 text-sm font-medium transition"
       style="@if(Route::currentRouteName() == 'users.password') background-color: var(--theme-primary-50); color: var(--theme-primary-700); border-color: var(--theme-primary-700); @endif">
        <svg class="@if(Route::currentRouteName() == 'users.password') @else text-neutral-400 group-hover:text-neutral-500 @endif mr-3 h-5 w-5 flex-shrink-0" 
             style="@if(Route::currentRouteName() == 'users.password') color: var(--theme-primary-500); @endif"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
        {{ __('Password') }}
    </a>
</nav>

<div class="mt-6 px-4">
    <a href="{{ route('users.create') }}" 
       class="block w-full px-4 py-2 text-sm font-medium text-center bg-white border rounded-lg transition"
       style="color: var(--theme-primary-600); border-color: var(--theme-primary-600);"
       onmouseover="this.style.backgroundColor='var(--theme-primary-50)'"
       onmouseout="this.style.backgroundColor='white'">
        {{ __('New User') }}
    </a>
</div>
