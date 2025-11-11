{{-- Web notifications list partial --}}
@if(isset($notifications) && $notifications->count() > 0)
    <ul class="divide-y divide-gray-200">
        @php
            $lastDate = null;
        @endphp
        @foreach($notifications as $notification)
            @php
                $notificationDate = $notification->created_at->format('M j, Y');
                $isToday = $notification->created_at->isToday();
            @endphp
            
            {{-- Date separator --}}
            @if($notificationDate !== $lastDate)
                <li class="px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    @if($isToday)
                        {{ __('Today') }}
                    @else
                        {{ $notificationDate }}
                    @endif
                </li>
                @php
                    $lastDate = $notificationDate;
                @endphp
            @endif
            
            {{-- Notification item --}}
            <li class="@if(!$notification->read_at) bg-blue-50 @endif hover:bg-gray-50 transition">
                <a href="{{ $notification->data['url'] ?? '#' }}" 
                   class="block px-4 py-3">
                    <div class="flex items-start space-x-3">
                        {{-- User avatar --}}
                        <div class="flex-shrink-0">
                            @if(isset($notification->data['user_photo']))
                                <img src="{{ $notification->data['user_photo'] }}" 
                                     alt="" 
                                     class="h-10 w-10 rounded-full">
                            @else
                                <div class="h-10 w-10 rounded-full bg-gray-400 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ isset($notification->data['user_name']) ? substr($notification->data['user_name'], 0, 2) : '?' }}
                                </div>
                            @endif
                        </div>
                        
                        {{-- Notification content --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-900">
                                {!! $notification->data['message'] ?? __('New notification') !!}
                            </div>
                            
                            @if(isset($notification->data['preview']))
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                    {{ $notification->data['preview'] }}
                                </p>
                            @endif
                            
                            <div class="mt-1 text-xs text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </div>
                        </div>
                        
                        {{-- Unread indicator --}}
                        @if(!$notification->read_at)
                            <div class="flex-shrink-0">
                                <span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span>
                            </div>
                        @endif
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@else
    {{-- Empty state --}}
    <div class="text-center py-12 px-4">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No notifications') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __("You're all caught up!") }}</p>
    </div>
@endif
