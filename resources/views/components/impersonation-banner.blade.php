<div>
    @if(auth()->check() && method_exists(auth()->user(), 'isImpersonated') && auth()->user()->isImpersonated())
        @php
            $ttlMinutes = \App\Http\Controllers\ImpersonationController::IMPERSONATION_TTL_MINUTES;
            $startedAt = session(\App\Http\Controllers\ImpersonationController::SESSION_STARTED_AT_KEY, now()->timestamp);
            $expiresAt = $startedAt + ($ttlMinutes * 60);
            $remainingSeconds = max(0, $expiresAt - now()->timestamp);
        @endphp
        <div
            x-data="{
                remaining: {{ $remainingSeconds }},
                init() {
                    this.tick();
                },
                tick() {
                    setInterval(() => {
                        this.remaining = Math.max(0, this.remaining - 1);
                        if (this.remaining <= 0) {
                            window.location.href = '{{ route('impersonate.leave.emergency') }}';
                        }
                    }, 1000);
                },
                get formatted() {
                    const m = Math.floor(this.remaining / 60);
                    const s = this.remaining % 60;
                    return m + ':' + String(s).padStart(2, '0');
                },
                get isUrgent() {
                    return this.remaining < 300;
                }
            }"
            class="bg-amber-100 border-b-4 border-amber-500 px-6 py-3 shadow-lg sticky top-0 z-[9999]"
        >
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">
                            <span class="uppercase tracking-wider font-bold">⚠️ Read-Only Mode:</span>
                            You are viewing the portal as <strong class="font-bold">{{ auth()->user()->name }}</strong>
                        </p>
                        <p class="text-xs text-amber-700">
                            All actions are disabled. Session expires in
                            <span
                                x-text="formatted"
                                :class="isUrgent ? 'text-red-700 font-bold' : 'font-semibold'"
                            ></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3 flex-shrink-0">
                    {{-- Emergency text link (GET — always works, no CSRF needed) --}}
                    <a
                        href="{{ route('impersonate.leave.emergency') }}"
                        class="text-xs text-amber-700 underline hover:text-amber-900"
                    >
                        Emergency exit
                    </a>

                    {{-- Primary exit button (POST) --}}
                    <form method="POST" action="{{ route('impersonate.leave') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            Exit & Return to Admin
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Keyboard shortcut: Escape key exits impersonation --}}
        <div x-data @keydown.escape.window="window.location.href = '{{ route('impersonate.leave.emergency') }}'"></div>
    @endif
</div>
