@extends('middleman::layouts.master')

@section('module-content')
    <div x-data="{
        mutedListeners: @json($mutedListeners),
        listenerCandidates: @json($listenerCandidates),
        listenerClass: '',
        submitting: false,
        async addMute() {
            if (!this.listenerClass.trim()) return;
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.muting.add') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ listener_class: this.listenerClass.trim() })
                });
                const data = await res.json();
                if (data.success) {
                    this.mutedListeners = data.muted_listeners || [];
                    this.listenerClass = '';
                }
            } finally {
                this.submitting = false;
            }
        },
        async removeMute(listenerClass) {
            const res = await fetch('{{ route('middleman.muting.remove') }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ listener_class: listenerClass })
            });
            const data = await res.json();
            if (data.success) {
                this.mutedListeners = data.muted_listeners || [];
            }
        },
        async clearAll() {
            this.submitting = true;
            try {
                const res = await fetch('{{ route('middleman.muting.clear') }}', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    this.mutedListeners = [];
                }
            } finally {
                this.submitting = false;
            }
        }
    }" class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">Listener Muting</h2>
            <span class="inline-flex items-center rounded-full bg-warning-100 px-3 py-1 text-xs font-medium text-warning-800"
                x-text="`${mutedListeners.length} Muted`"></span>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 p-5">
            <label class="block text-sm font-medium text-neutral-700 mb-2">Mute Listener Class</label>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" x-model="listenerClass" list="listener-candidates"
                    placeholder="App\\Listeners\\SendWelcomeEmail"
                    class="flex-1 rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                <button type="button" @click="addMute()" :disabled="submitting"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-xs font-semibold rounded-md hover:bg-primary-700 disabled:opacity-50">
                    Add Mute
                </button>
                <button type="button" @click="clearAll()" :disabled="submitting || mutedListeners.length === 0"
                    class="inline-flex items-center justify-center px-4 py-2 bg-danger-600 text-white text-xs font-semibold rounded-md hover:bg-danger-700 disabled:opacity-50">
                    Clear All
                </button>
            </div>
            <datalist id="listener-candidates">
                <template x-for="listener in listenerCandidates" :key="listener">
                    <option :value="listener"></option>
                </template>
            </datalist>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50">
                <h3 class="text-sm font-semibold text-neutral-800">Currently Muted Listeners</h3>
            </div>
            <div class="divide-y divide-neutral-100">
                <template x-if="mutedListeners.length === 0">
                    <div class="p-6 text-sm text-neutral-500 italic">No muted listeners right now.</div>
                </template>
                <template x-for="listener in mutedListeners" :key="listener">
                    <div class="p-4 flex items-center justify-between">
                        <code class="text-xs font-mono text-neutral-700 break-all" x-text="listener"></code>
                        <button type="button" @click="removeMute(listener)"
                            class="text-xs font-semibold text-danger-600 hover:text-danger-700">
                            Remove
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection
