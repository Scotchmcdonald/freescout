{{-- Bulk Actions Toolbar - Toolbar for bulk conversation operations --}}
<div class="bulk-actions-toolbar bg-white border-b border-gray-200 py-3 px-4" 
     x-data="{ 
         selected: [], 
         selectAll: false,
         showActions: false 
     }"
     x-init="$watch('selected', value => showActions = value.length > 0)">
    
    <div class="flex items-center justify-between">
        {{-- Select All Checkbox --}}
        <div class="flex items-center gap-4">
            <label class="flex items-center">
                <input type="checkbox" 
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       x-model="selectAll"
                       @change="selectAll ? selected = Array.from(document.querySelectorAll('.conversation-checkbox')).map(cb => cb.value) : selected = []">
                <span class="ml-2 text-sm text-gray-700">
                    {{ __('Select All') }}
                </span>
            </label>
            
            <span class="text-sm text-gray-500" x-show="showActions" style="display: none;">
                <span x-text="selected.length"></span> {{ __('selected') }}
            </span>
        </div>

        {{-- Bulk Actions --}}
        <div class="flex items-center gap-2" x-show="showActions" style="display: none;">
            {{-- Assign Action --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" 
                        @click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ __('Assign') }}
                </button>
                
                <div x-show="open" 
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10"
                     style="display: none;">
                    <div class="py-1">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Assign to me') }}
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Unassign') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Status Change Action --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" 
                        @click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('Status') }}
                </button>
                
                <div x-show="open" 
                     @click.away="open = false"
                     class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10"
                     style="display: none;">
                    <div class="py-1">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Active') }}
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Pending') }}
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Closed') }}
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            {{ __('Spam') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Move Action --}}
            <button type="button" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M3 7h.01M3 12h.01M3 17h.01"/>
                </svg>
                {{ __('Move') }}
            </button>

            {{-- Merge Action --}}
            <button type="button" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M3 7h.01M3 12h.01M3 17h.01"/>
                </svg>
                {{ __('Merge') }}
            </button>

            {{-- Delete Action --}}
            <button type="button" 
                    @click="if(confirm('{{ __('Are you sure you want to delete the selected conversations?') }}')) { /* handle delete */ }"
                    class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                {{ __('Delete') }}
            </button>
        </div>
    </div>
</div>
