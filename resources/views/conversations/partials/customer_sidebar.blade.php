{{-- Customer Sidebar Partial - Shows customer information in conversation view --}}
@if(isset($customer))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h4 class="text-lg font-semibold mb-4 text-gray-900">{{ __('Customer') }}</h4>
        
        {{-- Customer Avatar and Name --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold text-lg">
                {{ substr($customer->first_name ?? '', 0, 1) }}{{ substr($customer->last_name ?? '', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 truncate">
                    @if(method_exists($customer, 'url'))
                        <a href="{{ $customer->url() }}" class="text-blue-600 hover:underline">
                            {{ $customer->getFullName(true) }}
                        </a>
                    @else
                        {{ $customer->getFullName(true) }}
                    @endif
                </div>
                <div class="text-sm text-gray-500 truncate">
                    {{ $customer->email ?? ($customer->emails[0] ?? '') }}
                </div>
            </div>
        </div>

        {{-- Customer Details --}}
        <div class="space-y-3 text-sm">
            @if($customer->email || (!empty($customer->emails) && is_array($customer->emails)))
                <div>
                    <div class="text-gray-500 mb-1">{{ __('Email') }}</div>
                    <div class="text-gray-900">
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="text-blue-600 hover:underline">
                                {{ $customer->email }}
                            </a>
                        @elseif(!empty($customer->emails) && is_array($customer->emails))
                            @foreach($customer->emails as $email)
                                <a href="mailto:{{ $email }}" class="text-blue-600 hover:underline block">
                                    {{ $email }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

            @if(!empty($customer->phone))
                <div>
                    <div class="text-gray-500 mb-1">{{ __('Phone') }}</div>
                    <div class="text-gray-900">
                        <a href="tel:{{ $customer->phone }}" class="text-blue-600 hover:underline">
                            {{ $customer->phone }}
                        </a>
                    </div>
                </div>
            @endif

            @if(!empty($customer->company))
                <div>
                    <div class="text-gray-500 mb-1">{{ __('Company') }}</div>
                    <div class="text-gray-900">{{ $customer->company }}</div>
                </div>
            @endif

            @if($customer->created_at)
                <div>
                    <div class="text-gray-500 mb-1">{{ __('Customer Since') }}</div>
                    <div class="text-gray-900">{{ $customer->created_at->format('M d, Y') }}</div>
                </div>
            @endif
        </div>

        {{-- Previous Conversations --}}
        @if(isset($conversation) && $conversation->customer_id)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h5 class="font-medium text-gray-900 mb-3">{{ __('Recent Conversations') }}</h5>
                @include('conversations.partials.prev_convs_short', ['customer_id' => $customer->id, 'current_conversation_id' => $conversation->id ?? null])
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="space-y-2">
                @if(method_exists($customer, 'url'))
                    <a href="{{ $customer->url() }}" 
                       class="block w-full px-4 py-2 text-sm text-center bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        {{ __('View Customer Profile') }}
                    </a>
                @endif
                
                @can('update', $customer)
                    <a href="{{ route('customers.edit', $customer) }}" 
                       class="block w-full px-4 py-2 text-sm text-center bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                        {{ __('Edit Customer') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endif
