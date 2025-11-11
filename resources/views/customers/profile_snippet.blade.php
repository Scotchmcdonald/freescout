{{-- Customer quick info snippet --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    {{-- Customer photo --}}
    <div class="flex items-center mb-4">
        <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center text-white text-xl font-semibold flex-shrink-0">
            {{ substr($customer->first_name, 0, 1) }}{{ substr($customer->last_name ?? '', 0, 1) }}
        </div>
        <div class="ml-3 flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 truncate">
                {{ $customer->getFullName() }}
            </h3>
            @if($customer->company)
                <p class="text-sm text-gray-600 truncate">{{ $customer->company }}</p>
            @endif
        </div>
    </div>
    
    {{-- Contact information --}}
    <div class="space-y-2 text-sm">
        @if($customer->emails && count($customer->emails))
            <div>
                <div class="text-gray-500 mb-1">{{ __('Email') }}</div>
                @foreach($customer->emails as $email)
                    <div class="flex items-center justify-between group">
                        <a href="mailto:{{ $email['email'] ?? '' }}" 
                           class="text-blue-600 hover:text-blue-800 truncate">
                            {{ $email['email'] ?? '' }}
                        </a>
                        <button type="button" 
                                onclick="navigator.clipboard.writeText('{{ $email['email'] ?? '' }}')"
                                class="ml-2 text-gray-400 hover:text-gray-600 opacity-0 group-hover:opacity-100 transition"
                                title="{{ __('Copy') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        
        @if($customer->phones && count($customer->phones))
            <div>
                <div class="text-gray-500 mb-1">{{ __('Phone') }}</div>
                @foreach($customer->phones as $phone)
                    <a href="tel:{{ $phone['number'] ?? '' }}" 
                       class="block text-gray-700 hover:text-blue-600">
                        {{ $phone['number'] ?? '' }}
                        @if(!empty($phone['type']) && $phone['type'] != 'work')
                            <span class="text-xs text-gray-500">({{ ucfirst($phone['type']) }})</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
        
        @if($customer->job_title)
            <div>
                <div class="text-gray-500 mb-1">{{ __('Job Title') }}</div>
                <div class="text-gray-700">{{ $customer->job_title }}</div>
            </div>
        @endif
        
        @php
            $location = array_filter([$customer->city, $customer->state, $customer->country]);
        @endphp
        @if($customer->address || $location)
            <div>
                <div class="text-gray-500 mb-1">{{ __('Location') }}</div>
                @if($customer->address)
                    <div class="text-gray-700">{{ $customer->address }}</div>
                @endif
                @if($customer->city || $customer->state)
                    <div class="text-gray-700">
                        {{ $customer->city }}{{ $customer->state ? ', ' . $customer->state : '' }} {{ $customer->zip }}
                    </div>
                @endif
                @if($customer->country)
                    <div class="text-gray-700">{{ $customer->country }}</div>
                @endif
            </div>
        @endif
        
        @if($customer->notes)
            <div>
                <div class="text-gray-500 mb-1">{{ __('Notes') }}</div>
                <div class="text-gray-700 italic text-xs">{{ $customer->notes }}</div>
            </div>
        @endif
        
        <div>
            <div class="text-gray-500 mb-1">{{ __('Customer Since') }}</div>
            <div class="text-gray-700">{{ $customer->created_at->format('M d, Y') }}</div>
        </div>
    </div>
</div>
