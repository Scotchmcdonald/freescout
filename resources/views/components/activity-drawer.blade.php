<div x-data="activityDrawer()" 
     @open-activity-drawer.window="open = true"
     class="relative z-50" 
     aria-labelledby="slide-over-title" 
     role="dialog" 
     aria-modal="true"
     x-show="open"
     style="display: none;">
    
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
         x-show="open"
         x-transition:enter="ease-in-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"></div>

    <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md"
                     x-show="open"
                     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full">
                    
                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        <div class="px-4 py-6 sm:px-6 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-start justify-between">
                                <h2 class="text-lg font-medium text-gray-900" id="slide-over-title">System Activity</h2>
                                <div class="ml-3 flex h-7 items-center">
                                    <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="open = false">
                                        <span class="sr-only">Close panel</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="relative mt-6 flex-1 px-4 sm:px-6">
                            <!-- Active Jobs -->
                            <template x-for="job in jobs" :key="job.id">
                                <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-medium text-gray-900" x-text="job.name"></span>
                                        <x-status-badge status="processing" />
                                    </div>
                                    <div class="mb-2">
                                        <x-progress-bar :percent="0" :alpine="'job.progress'" />
                                    </div>
                                    <p class="text-xs text-gray-500 font-mono" x-text="job.status_text"></p>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <div x-show="jobs.length === 0" class="text-center text-gray-500 py-10">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <p class="mt-2 text-sm">No active background jobs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function activityDrawer() {
    return {
        open: false,
        jobs: [],
        init() {
            // Listen for global events
            window.addEventListener('job-started', (e) => {
                this.jobs.push(e.detail);
                this.open = true;
            });
            
            window.addEventListener('job-progress', (e) => {
                let job = this.jobs.find(j => j.id === e.detail.id);
                if (job) {
                    job.progress = e.detail.progress;
                    job.status_text = e.detail.status_text;
                }
            });

            window.addEventListener('job-completed', (e) => {
                this.jobs = this.jobs.filter(j => j.id !== e.detail.id);
            });

            // Listen for Echo events
            if (typeof Echo !== 'undefined') {
                Echo.private('App.Models.User.{{ auth()->id() }}')
                    .listen('.job.updated', (e) => {
                        if (e.status === 'started') {
                            // Avoid duplicates
                            if (!this.jobs.find(j => j.id === e.jobId)) {
                                this.jobs.push({
                                    id: e.jobId,
                                    name: e.name,
                                    progress: e.progress,
                                    status_text: e.statusText
                                });
                                this.open = true;
                            }
                        } else if (e.status === 'completed') {
                            this.jobs = this.jobs.filter(j => j.id !== e.jobId);
                        } else {
                            let job = this.jobs.find(j => j.id === e.jobId);
                            if (job) {
                                job.progress = e.progress;
                                job.status_text = e.statusText;
                            }
                        }
                    });
            }
        }
    }
}
</script>