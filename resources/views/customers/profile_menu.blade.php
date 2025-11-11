{{-- Customer profile dropdown menu --}}
<div class="relative inline-block" x-data="{ open: false }">
    <button @click="open = !open" 
            class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition"
            type="button">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>
    
    <div x-show="open" 
         @click.away="open = false"
         x-cloak
         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
        <a href="{{ route('customers.edit', $customer) }}" 
           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            {{ __('Edit Customer') }}
        </a>
        @can('delete', $customer)
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" 
                  onsubmit="return confirm('{{ __('Are you sure you want to delete this customer?') }}');"
                  class="block">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                    {{ __('Delete Customer') }}
                </button>
            </form>
        @endcan
    </div>
</div>
