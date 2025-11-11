{{-- Notification subscriptions table --}}
@php
    use App\Models\Subscription;
    $person = $person ?? __('me');
    $subscriptions = $subscriptions ?? collect();
    $mobile_available = $mobile_available ?? false;
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                    @if($person !== __('me'))
                        {{ __('Notify :person when…', ['person' => $person]) }}
                    @else
                        {{ __('Notify me when…') }}
                    @endif
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-24">
                    {{ __('Email') }}<br>
                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all-email">
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-24">
                    {{ __('Browser') }}<br>
                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all-browser">
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-24">
                    {{ __('Mobile') }}<br>
                    <input type="checkbox" 
                           class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all-mobile" 
                           @if(!$mobile_available) disabled @endif>
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            {{-- General notifications --}}
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('There is a new conversation') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_NEW_CONVERSATION])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_NEW_CONVERSATION }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_NEW_CONVERSATION])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_NEW_CONVERSATION }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_NEW_CONVERSATION])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_NEW_CONVERSATION }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">
                    @if($person !== __('me'))
                        {{ __('A conversation is assigned to :person', ['person' => $person]) }}
                    @else
                        {{ __('A conversation is assigned to me') }}
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('A conversation is assigned to someone else') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_CONVERSATION_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_CONVERSATION_ASSIGNED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">
                    @if($person !== __('me'))
                        {{ __('A conversation :person is following is updated', ['person' => $person]) }}
                    @else
                        {{ __("A conversation I'm following is updated") }}
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            {{-- Customer replies section --}}
            <tr class="bg-gray-50">
                <th colspan="4" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                    @if($person !== __('me'))
                        {{ __("Notify :person when a customer replies…", ['person' => $person]) }}
                    @else
                        {{ __('Notify me when a customer replies…') }}
                    @endif
                </th>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('To an unassigned conversation') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">
                    @if($person !== __('me'))
                        {{ __('To a conversation assigned to :person', ['person' => $person]) }}
                    @else
                        {{ __('To a conversation assigned to me') }}
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_MY }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('To a conversation assigned to someone else') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            {{-- User replies section --}}
            <tr class="bg-gray-50">
                <th colspan="4" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                    @if($person !== __('me'))
                        {{ __("Notify :person when another user replies or adds a note…", ['person' => $person]) }}
                    @else
                        {{ __('Notify me when another user replies or adds a note…') }}
                    @endif
                </th>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('To an unassigned conversation') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">
                    @if($person !== __('me'))
                        {{ __('To a conversation assigned to :person', ['person' => $person]) }}
                    @else
                        {{ __('To a conversation assigned to me') }}
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_USER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_MY }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_USER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_MY }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_USER_REPLIED_TO_MY])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_MY }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
            
            <tr>
                <td class="px-6 py-4 text-sm text-gray-900">{{ __('To a conversation assigned to someone else') }}</td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_EMAIL, 'event' => Subscription::EVENT_USER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_EMAIL }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-email">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_BROWSER, 'event' => Subscription::EVENT_USER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_BROWSER }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-browser">
                </td>
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" 
                           @include('users.is_subscribed', ['medium' => Subscription::MEDIUM_MOBILE, 'event' => Subscription::EVENT_USER_REPLIED_TO_ASSIGNED])
                           name="subscriptions[{{ Subscription::MEDIUM_MOBILE }}][]" 
                           value="{{ Subscription::EVENT_USER_REPLIED_TO_ASSIGNED }}"
                           @if(!$mobile_available) disabled @endif
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 subscription-mobile">
                </td>
            </tr>
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all checkboxes for each column
        document.querySelector('.select-all-email')?.addEventListener('change', function(e) {
            document.querySelectorAll('.subscription-email').forEach(cb => cb.checked = e.target.checked);
        });
        
        document.querySelector('.select-all-browser')?.addEventListener('change', function(e) {
            document.querySelectorAll('.subscription-browser').forEach(cb => cb.checked = e.target.checked);
        });
        
        document.querySelector('.select-all-mobile')?.addEventListener('change', function(e) {
            document.querySelectorAll('.subscription-mobile:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
        });
    });
</script>
@endpush
