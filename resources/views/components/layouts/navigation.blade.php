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
                            $canManageMailboxes = Auth::user()->hasAdminAccess();
                        @endphp
                        @if ($mailboxes->count() > 0 || $canManageMailboxes)
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
                                    <x-menu-heading>{{ __('General') }}</x-menu-heading>
                                    <x-dropdown-link :href="route('mailboxes.index')">
                                        {{ __('All Mailboxes') }}
                                    </x-dropdown-link>
                                    @if ($canManageMailboxes)
                                        <x-dropdown-link :href="route('mailboxes.create')">
                                            {{ __('Create Mailbox') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if ($mailboxes->count() > 0)
                                        <x-menu-heading bordered>{{ __('Mailboxes') }}</x-menu-heading>
                                        @foreach ($mailboxes as $mailbox)
                                            <x-dropdown-link :href="route('mailboxes.view', ['mailbox' => $mailbox->id])" class="ps-6">
                                                {{ $mailbox->name }}
                                            </x-dropdown-link>
                                        @endforeach
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                        @endif
                    @endauth

                    <!-- Manage (Dynamic Items) -->
                    @inject('navigationService', 'App\Services\Navigation\NavigationService')
                    @if(count($navigationService->getItems()) > 0)
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
                                    @foreach($navigationService->getGroupedItems() as $category => $items)
                                        @if($category === 'General')
                                            @if(count($items) > 0)
                                                <x-menu-heading>{{ __('General') }}</x-menu-heading>
                                            @endif
                                            @foreach($items as $item)
                                                @if($item['type'] === 'dropdown')
                                                     @foreach($item['children'] as $child)
                                                        @if(empty($child['permission']) || Gate::check($child['permission']))
                                                            <x-dropdown-link :href="route($child['route'])" class="ps-6">
                                                                {{ __($child['label']) }}
                                                            </x-dropdown-link>
                                                        @endif
                                                    @endforeach
                                                @elseif($item['type'] === 'link')
                                                     @if(empty($item['permission']) || Gate::check($item['permission']))
                                                        <x-dropdown-link :href="route($item['route'])">
                                                            {{ __($item['label']) }}
                                                        </x-dropdown-link>
                                                     @endif
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="relative group" x-data="{ subOpen: false }" @mouseenter="subOpen = true" @mouseleave="subOpen = false">
                                                <button
                                                    type="button"
                                                    class="w-full text-start px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-500 hover:bg-neutral-50 focus:outline-none focus:bg-neutral-50 transition duration-150 ease-in-out flex justify-between items-center"
                                                    aria-haspopup="menu"
                                                    :aria-expanded="subOpen"
                                                >
                                                    <span>{{ __($category) }}</span>
                                                    <svg class="h-4 w-4 text-neutral-400 group-hover:text-neutral-500 transform rotate-90 rtl:-rotate-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <div x-show="subOpen"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     x-transition:leave="transition ease-in duration-75"
                                                     x-transition:leave-start="opacity-100 scale-100"
                                                     x-transition:leave-end="opacity-0 scale-95"
                                                     class="absolute top-0 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1"
                                                     style="left: 100%; display: none;">
                                                    @php
                                                        $firstSubItem = $items[0] ?? null;
                                                        $firstHasDesignatedHeading = is_array($firstSubItem)
                                                            && ($firstSubItem['type'] ?? null) === 'dropdown'
                                                            && ($firstSubItem['label'] ?? null) !== $category;
                                                    @endphp
                                                    @if(!$firstHasDesignatedHeading)
                                                        <x-menu-heading>{{ __($category) }}</x-menu-heading>
                                                    @endif
                                                    @foreach($items as $item)
                                                        @if($item['type'] === 'dropdown')
                                                            @if($item['label'] !== $category)
                                                                <x-menu-heading>{{ __($item['label']) }}</x-menu-heading>
                                                            @endif
                                                            @foreach($item['children'] as $child)
                                                                 @if(empty($child['permission']) || Gate::check($child['permission']))
                                                                    <x-dropdown-link :href="route($child['route'])" class="ps-6">
                                                                        {{ __($category === 'Customers' && $item['label'] === 'Portal' && $child['label'] === 'Clients' ? 'CRM' : $child['label']) }}
                                                                    </x-dropdown-link>
                                                                @endif
                                                            @endforeach
                                                        @elseif($item['type'] === 'link')
                                                             @if(empty($item['permission']) || Gate::check($item['permission']))
                                                                <x-dropdown-link :href="route($item['route'])">
                                                                    {{ __($item['label']) }}
                                                                </x-dropdown-link>
                                                             @endif
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                    <?php app('eventy')->action('menu.manage'); ?>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    <!-- Admin -->
                    @if (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasPermission(App\Models\User::PERM_EDIT_USERS)))
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ __('Admin') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @php
                                        $showAdminConfiguration = Auth::user()->isAdmin();
                                    @endphp

                                    @if (Auth::user()->isAdmin())
                                        <x-menu-heading>{{ __('Administration') }}</x-menu-heading>
                                        <x-dropdown-link :href="route('settings')">
                                            {{ __('Settings') }}
                                        </x-dropdown-link>

                                        @if(Route::has('pib.workbench.index'))
                                        <x-dropdown-link :href="route('pib.workbench.index')">
                                            {{ __('Billing Workbench') }}
                                        </x-dropdown-link>
                                        @endif
                                    @endif

                                    @if (Auth::user()->isAdmin() || Auth::user()->hasPermission(App\Models\User::PERM_EDIT_USERS))
                                        <x-menu-heading :bordered="$showAdminConfiguration">{{ __('Access Control') }}</x-menu-heading>
                                        <x-dropdown-link :href="route('users.index')">
                                            {{ __('Users') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    <!-- System -->
                    @if (Auth::check() && Auth::user()->isAdmin())
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-inherit bg-transparent hover:opacity-75 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ __('System') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-menu-heading>{{ __('System') }}</x-menu-heading>
                                    <x-dropdown-link :href="route('modules')">
                                        {{ __('Modules') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('logs')">
                                        {{ __('Logs') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('system')">
                                        {{ __('System') }}
                                    </x-dropdown-link>

                                    <x-menu-heading bordered>{{ __('Resilience') }}</x-menu-heading>
                                    <x-dropdown-link :href="route('admin.resilience.index')" class="ps-6">
                                        {{ __('Resilience') }}
                                    </x-dropdown-link>

                                    {{-- Infrastructure Resilience (Phase 6) --}}
                                    @if(Route::has('admin.resilience.events-audit'))
                                    <x-dropdown-link :href="route('admin.resilience.events-audit')" class="ps-6">
                                        {{ __('Event Audit Log') }}
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

                <button type="button" @click="$dispatch('open-activity-drawer')" class="text-inherit hover:opacity-75 focus:outline-none mr-2" title="{{ __('View Activity') }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </button>

                <button id="theme-toggle" type="button" x-data="themeToggle" @click="toggle()" class="text-inherit hover:opacity-75 focus:outline-none mr-2" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5 {{ Auth::user()->dark_mode ? '' : 'hidden' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="w-5 h-5 {{ Auth::user()->dark_mode ? 'hidden' : '' }}" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <!-- Style Guide Link -->
                <a href="{{ route('themes') }}" class="text-inherit hover:opacity-75 focus:outline-none mr-4" aria-label="Themes & Style Guide" title="Themes & Style Guide">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd"></path>
                    </svg>
                </a>

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
                        <x-menu-heading>{{ __('Profile') }}</x-menu-heading>
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-menu-heading bordered>{{ __('Session') }}</x-menu-heading>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" class="ps-6"
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
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-neutral-400 hover:text-neutral-500 hover:bg-neutral-100 focus:outline-none focus:bg-neutral-100 focus:text-neutral-500 transition duration-150 ease-in-out">
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
            <x-menu-heading>{{ __('General') }}</x-menu-heading>
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-neutral-200">
            <div class="px-4">
                <div class="font-medium text-base text-neutral-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-neutral-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-menu-heading>{{ __('Profile') }}</x-menu-heading>
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-menu-heading bordered>{{ __('Session') }}</x-menu-heading>

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
