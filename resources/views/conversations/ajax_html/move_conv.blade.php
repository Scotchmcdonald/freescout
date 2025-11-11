{{-- Move Conversation Dialog - Move conversation to different mailbox --}}
<div class="move-conv-dialog p-6">
    <form method="POST" 
          action="{{ route('conversations.move', $conversation->id ?? 0) }}">
        @csrf
        
        <div class="space-y-6">
            {{-- Current Mailbox --}}
            @if(isset($conversation) && $conversation->mailbox)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">{{ __('Current Mailbox') }}</div>
                    <div class="font-medium text-gray-900">{{ $conversation->mailbox->name }}</div>
                </div>
            @endif

            {{-- Mailbox Selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Move to Mailbox') }}
                </label>
                <select name="mailbox_id" 
                        required
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('Select a mailbox...') }}</option>
                    @foreach(\App\Models\Mailbox::orderBy('name')->get() as $mailbox)
                        @if(!isset($conversation) || $mailbox->id != $conversation->mailbox_id)
                            <option value="{{ $mailbox->id }}">{{ $mailbox->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Confirmation Info --}}
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            {{ __('Moving this conversation will change its mailbox and may affect permissions and workflows.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" 
                    onclick="window.parent.closeModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                {{ __('Cancel') }}
            </button>
            <button type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition">
                {{ __('Move Conversation') }}
            </button>
        </div>
    </form>
</div>
