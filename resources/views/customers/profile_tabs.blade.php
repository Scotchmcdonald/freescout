{{-- Customer profile tabs navigation --}}
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="{{ route('customers.edit', $customer) }}" 
           class="@if(Route::currentRouteName() == 'customers.edit') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
            {{ __('Edit Profile') }}
        </a>
        
        <a href="{{ route('customers.show', $customer) }}" 
           class="@if(Route::currentRouteName() == 'customers.show') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
            {{ __('Conversations') }}
        </a>
        
        @if(!empty($extra_tab))
            <a href="#" 
               class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ $extra_tab }}
            </a>
        @endif
    </nav>
</div>
