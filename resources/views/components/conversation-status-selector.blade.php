@props(['conversation', 'updateUrl'])

@php
    use App\Enums\ConversationStatus;
@endphp

<div x-data="{
    conversationId: {{ $conversation->id }},
    status: {{ $conversation->status }},
    loading: false,
    updateUrl: '{{ $updateUrl }}',
    
    async updateStatus(newStatus) {
        this.loading = true;
        
        try {
            const response = await fetch(this.updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    status: newStatus,
                    action: 'update_status'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.status = newStatus;
                // Show success notification (if you have a notification system)
                window.dispatchEvent(new CustomEvent('notification', {
                    detail: { type: 'success', message: 'Status updated successfully' }
                }));
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            // Show error notification
            window.dispatchEvent(new CustomEvent('notification', {
                detail: { type: 'error', message: error.message }
            }));
        } finally {
            this.loading = false;
        }
    }
}">
    <div class="flex items-center gap-2">
        <select 
            class="border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
            x-model="status"
            @change="updateStatus($event.target.value)"
            :disabled="loading"
        >
            @foreach(ConversationStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}">
                    {{ $statusOption->label() }}
                </option>
            @endforeach
        </select>
        
        <span x-show="loading" x-cloak class="text-sm text-gray-500 flex items-center">
            <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating...
        </span>
    </div>
</div>
