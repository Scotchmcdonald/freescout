<div>
    @if(auth()->check() && auth()->user()->isImpersonated())
        <div class="bg-amber-100 border-b-4 border-amber-500 px-6 py-3 shadow-lg">
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
                        <p class="text-xs text-amber-700">All actions are disabled. No changes can be made in this mode.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('impersonate.leave') }}" class="flex-shrink-0">
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
    @endif
</div>
