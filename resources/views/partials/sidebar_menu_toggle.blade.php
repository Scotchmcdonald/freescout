{{-- Sidebar Menu Toggle Component --}}
{{-- Mobile hamburger menu toggle button with Alpine.js --}}

<button 
    type="button" 
    @click="sidebarOpen = !sidebarOpen"
    class="sidebar-menu-toggle inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-colors duration-150"
    aria-expanded="false"
    aria-label="{{ __('Toggle Navigation') }}"
>
    <span class="sr-only">{{ __('Toggle Navigation') }}</span>
    
    {{-- Hamburger icon --}}
    <svg 
        class="h-6 w-6" 
        x-show="!sidebarOpen"
        fill="none" 
        viewBox="0 0 24 24" 
        stroke-width="1.5" 
        stroke="currentColor"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
    </svg>
    
    {{-- Close icon --}}
    <svg 
        class="h-6 w-6" 
        x-show="sidebarOpen"
        x-cloak
        fill="none" 
        viewBox="0 0 24 24" 
        stroke-width="1.5" 
        stroke="currentColor"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
