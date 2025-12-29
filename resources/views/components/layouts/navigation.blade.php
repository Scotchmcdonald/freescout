<nav x-data="{ open: false }" class="theme-nav border-b">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @auth
                        @php
                            $mailboxes = Auth::user()->mailboxesCanView(true);
                        @endphp
                        @if ($mailboxes->count() == 1)
                            <x-nav-link :href="route('mailboxes.view', ['mailbox' => $mailboxes->first()->id])" :active="request()->routeIs('mailboxes.view') && request()->mailbox && request()->mailbox->id == $mailboxes->first()->id">
                                {{ __('Mailbox') }}
                            </x-nav-link>
                        @elseif ($mailboxes->count() > 1)
                             <div class="hidden sm:flex sm:items-center sm:ms-6">
                                <x-dropdown align="left" width="48">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                                            <div>{{ __('Mailboxes') }}</div>
                                            <div class="ms-1">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        @foreach ($mailboxes as $mailbox)
                                            <x-dropdown-link :href="route('mailboxes.view', ['mailbox' => $mailbox->id])">
                                                {{ $mailbox->name }}
                                            </x-dropdown-link>
                                        @endforeach
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        @endif
                    @endauth

                    <!-- Dynamic Navigation -->
                    @inject('navigationService', 'App\Services\Navigation\NavigationService')
                    @foreach($navigationService->getItems() as $item)
                        @if($item['type'] === 'dropdown')
                            @if(empty($item['permission']) || Gate::check($item['permission']))
                                <div class="hidden sm:flex sm:items-center sm:ms-6">
                                    <x-dropdown align="left" width="48">
                                        <x-slot name="trigger">
                                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                                                <div>{{ __($item['label']) }}</div>
                                                <div class="ms-1">
                                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            @foreach($item['children'] as $child)
                                                @if(empty($child['permission']) || Gate::check($child['permission']))
                                                    @if(Route::has($child['route']))
                                                        <x-dropdown-link :href="route($child['route'])">
                                                            {{ __($child['label']) }}
                                                        </x-dropdown-link>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            @endif
                        @elseif($item['type'] === 'link')
                             @if(empty($item['permission']) || Gate::check($item['permission']))
                                @if(Route::has($item['route']))
                                    <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])">
                                        {{ __($item['label']) }}
                                    </x-nav-link>
                                @endif
                             @endif
                        @endif
                    @endforeach

                    @if (Auth::check() && (Auth::user()->isAdmin()
                        || Auth::user()->hasPermission(App\Models\User::PERM_EDIT_USERS))
                    )
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ __('Manage') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @if (Auth::user()->isAdmin())
                                        <x-dropdown-link :href="route('settings')">
                                            {{ __('Settings') }}
                                        </x-dropdown-link>
                                    @endif
                                    
                                    <x-dropdown-link :href="route('mailboxes.index')">
                                        {{ __('Mailboxes') }}
                                    </x-dropdown-link>

                                    @if (Auth::user()->isAdmin() || Auth::user()->hasPermission(App\Models\User::PERM_EDIT_USERS))
                                        <x-dropdown-link :href="route('users.index')">
                                            {{ __('Users') }}
                                        </x-dropdown-link>
                                    @endif

                                    @if (Auth::user()->isAdmin())
                                        <x-dropdown-link :href="route('modules')">
                                            {{ __('Modules') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('logs')">
                                            {{ __('Logs') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('system')">
                                            {{ __('System') }}
                                        </x-dropdown-link>
                                        
                                        <x-dropdown-link :href="route('themes')">
                                            {{ __('Themes & Style Guide') }}
                                        </x-dropdown-link>

                                        <div class="border-t border-gray-100 my-1"></div>

                                        @if(Route::has('inventory.products.index'))
                                        <x-dropdown-link :href="route('inventory.products.index')">
                                            {{ __('Product Catalog') }}
                                        </x-dropdown-link>
                                        @endif

                                        @action('menu.manage')
                                    @else
                                        <x-dropdown-link :href="route('themes')">
                                            {{ __('Themes') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            @auth
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Theme Toggle -->
                <button id="theme-toggle" type="button" x-data="themeToggle" @click="toggle()" class="text-inherit hover:opacity-75 focus:outline-none mr-4" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5 {{ Auth::user()->dark_mode ? '' : 'hidden' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="w-5 h-5 {{ Auth::user()->dark_mode ? 'hidden' : '' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @endauth

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>
