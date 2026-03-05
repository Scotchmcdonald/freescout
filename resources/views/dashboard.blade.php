<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @filter('dashboard.before', '')

            {{-- Welcome bar --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">
                        Welcome back, {{ $user->full_name }}! @action('dashboard.heading_append')
                    </h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        @if($user->isAdmin())
                            Operations overview — {{ now()->format('l, F j, Y') }}
                        @elseif($user->isFinance())
                            Billing & receivables — {{ now()->format('l, F j, Y') }}
                        @elseif($user->isReporter())
                            Business summary — {{ now()->format('l, F j, Y') }}
                        @else
                            Your queue — {{ now()->format('l, F j, Y') }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Role-differentiated widget zone --}}
            @if(!empty($dashboardWidgetsHtml))
                <div class="mb-8">
                    {!! $dashboardWidgetsHtml !!}
                </div>
            @endif

            {{-- Support / Mailbox panel (all roles) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6">

                    {{-- Conversation stat cards --}}
                    <div id="dashboard-stats" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-green-50 border border-green-200 p-4 rounded-xl">
                            <div class="text-2xl font-bold text-green-800">{{ $mailboxes->count() }}</div>
                            <div class="text-sm text-green-600 mt-1">Mailboxes</div>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl">
                            <div class="text-2xl font-bold text-yellow-800">{{ $activeConversations }}</div>
                            <div class="text-sm text-yellow-600 mt-1">Active Conversations</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 p-4 rounded-xl">
                            <div class="text-2xl font-bold text-red-800">{{ $unassignedConversations }}</div>
                            <div class="text-sm text-red-600 mt-1">Unassigned</div>
                        </div>
                    </div>

                    {{-- Mailbox list --}}
                    <div id="dashboard-mailboxes" class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Your Mailboxes</h4>
                        <ul class="space-y-1.5">
                            @forelse($mailboxes as $mailbox)
                                <li class="flex items-center justify-between border-l-4 border-indigo-400 pl-3 py-1">
                                    @action('dash_card.before_mailbox_name', $mailbox)
                                    <a href="{{ route('mailboxes.view', $mailbox->id) }}"
                                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                        {{ $mailbox->name }}
                                        <span class="text-gray-400 font-normal">({{ $mailbox->email }})</span>
                                    </a>
                                    @if(isset($stats[$mailbox->id]))
                                        <span class="text-xs text-gray-500">
                                            {{ $stats[$mailbox->id]['active'] }} active
                                            @if($stats[$mailbox->id]['unassigned'] > 0)
                                                · <span class="text-red-500">{{ $stats[$mailbox->id]['unassigned'] }} unassigned</span>
                                            @endif
                                        </span>
                                    @endif
                                </li>
                            @empty
                                <li class="text-sm text-gray-500 italic">No mailboxes assigned.</li>
                            @endforelse
                        </ul>
                    </div>

                    @action('dashboard.modules')

                </div>
            </div>

            @filter('dashboard.after', '')

        </div>
    </div>
</x-app-layout>

