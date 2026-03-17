{{-- Customer profile tabs navigation --}}
<div class="border-b border-neutral-200 mb-6">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="{{ route('customers.edit', $customer) }}" 
           class="@if(Route::currentRouteName() == 'customers.edit') border-primary-500 text-primary-600 @else border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
            {{ __('Edit Profile') }}
        </a>
        
        <a href="{{ route('customers.show', $customer) }}" 
           class="@if(Route::currentRouteName() == 'customers.show') border-primary-500 text-primary-600 @else border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
            {{ __('Conversations') }}
        </a>
        
        @if(!empty($extra_tab))
            <a href="#" 
               class="border-primary-500 text-primary-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ $extra_tab }}
            </a>
        @endif
    </nav>
</div>
