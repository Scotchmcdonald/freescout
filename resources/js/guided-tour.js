/**
 * Guided Tour Alpine.js Component
 *
 * Multi-page onboarding system using Driver.js. Supports:
 *   - Depth-based tours (high-level, detailed, whats-new)
 *   - Multi-page persistence across navigations and refreshes
 *   - Role-based access (filtered server-side)
 *   - Version-aware auto-reset when tour content changes
 *   - Analytics tracking for user behavior (views, choices, drop-offs)
 *
 * @see config/tours.php for tour definitions
 * @see docs/development/WIP/guided_tour_feature.md for spec
 */
export function guidedTour(config = {}) {
    return {
        /** @type {Object} UI Labels for I18n */
        labels: {
            next: 'Next →',
            prev: '← Back',
            finish: 'Finish',
            done: 'Done',
            progress: '{{current}} of {{total}}',
            ...config.labels
        },

        /** @type {Object|null} Driver.js instance */
        driverInstance: null,

        /** @type {Object} Map of tour_id -> tour metadata from server */
        availableTours: {},

        /** @type {string|null} Currently active tour ID */
        activeTourId: null,

        /** @type {Array} Steps for the active tour */
        activeSteps: [],

        /** @type {string} Current depth selection */
        activeDepth: 'high-level',

        /** @type {number} Current step index (mirrors server state) */
        currentStepIndex: 0,

        /** @type {boolean} Whether tours are currently loading */
        isLoading: false,

        /** @type {boolean} Whether the tour picker modal is open */
        showPicker: false,

        /** @type {boolean} Whether Driver.js module is loaded */
        driverLoaded: false,

        /** @type {string} Session storage key prefix */
        storagePrefix: 'guided_tour_',

        /** @type {number} Timestamp when the current step started */
        stepStartTime: null,

        /**
         * Alpine init lifecycle hook. Checks for a pending tour to resume.
         */
        async init() {
             // Expose checkpoint handler globally so onclick events in HTML strings can reach it
            window.guidedTourCheckpoint = (type, target) => {
                this.handleCheckpointAction(type, target);
            };

            await this.loadAvailableTours();
            this.checkForPendingResume();
        },

        /**
         * Fetch available tours (with progress) from server.
         */
        async loadAvailableTours() {
            try {
                const response = await fetch('/tours', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!response.ok) return;

                const data = await response.json();
                this.availableTours = data.tours || {};
            } catch (error) {
                console.error('[GuidedTour] Failed to load tours:', error);
            }
        },

        /**
         * Check sessionStorage for a tour that was mid-flight during a page navigation.
         */
        checkForPendingResume() {
            const pending = sessionStorage.getItem(this.storagePrefix + 'pending');
            if (!pending) return;

            try {
                const { tourId, depth, stepIndex } = JSON.parse(pending);
                const currentPath = window.location.pathname;

                // Fetch the tour to find the step that matches this page
                this.resumeTour(tourId, depth, stepIndex, currentPath);
            } catch (e) {
                sessionStorage.removeItem(this.storagePrefix + 'pending');
            }
        },

        /**
         * Resume a tour after a page navigation.
         */
        async resumeTour(tourId, depth, stepIndex, currentPath) {
            sessionStorage.removeItem(this.storagePrefix + 'pending');

            try {
                const response = await fetch(`/tours/${tourId}?depth=${depth}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!response.ok) return;

                const data = await response.json();
                const steps = data.steps || [];

                // Find the step that matches the current page
                const targetStep = steps[stepIndex];
                if (!targetStep) return;

                // If the step requires a specific page and we're not on it, bail
                if (targetStep.page) {
                     // Check if path matches exactly or if it is a prefix (for sub-routes)
                     // If step page is /knowledgebase, we want to allow /knowledgebase/create... wait, tour steps are specific.
                     // The logic was causing issues with Explore page being treated as Index.
                     // Strict match is safest for now.
                     const isCurrentPage = currentPath === targetStep.page;
                     
                     if (!isCurrentPage) return;
                }

                // Start the tour from the correct step
                this.activeTourId = tourId;
                this.activeDepth = depth;
                this.activeSteps = steps;
                this.currentStepIndex = stepIndex;

                await this.ensureDriverLoaded();
                this.startDriverAtStep(stepIndex);
            } catch (error) {
                console.error('[GuidedTour] Failed to resume tour:', error);
            }
        },

        /**
         * Start a tour from scratch.
         */
        async startTour(tourId, depth = 'high-level') {
            console.log('[GuidedTour] Starting tour:', tourId, depth);
            this.isLoading = true;
            this.showPicker = false;
            
            // Clear any pending resume to avoid conflicts
            sessionStorage.removeItem(this.storagePrefix + 'pending');

            try {
                const response = await fetch(`/tours/${tourId}?depth=${depth}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                if (!response.ok) {
                    const err = await response.json();
                    console.error('[GuidedTour] Error:', err.error);
                    return;
                }

                const data = await response.json();

                this.activeTourId = tourId;
                this.activeDepth = depth;
                this.activeSteps = data.steps || [];
                // If resuming via logic (not storage), prioritize server state unless completed
                this.currentStepIndex = data.progress?.is_completed ? 0 : (data.progress?.current_step_index || 0);

                // If resuming a completed tour, reset server-side
                if (data.progress?.is_completed) {
                    await this.resetTourProgress(tourId, depth);
                }

                await this.ensureDriverLoaded();

                // Check if first step requires a different page
                const firstStep = this.activeSteps[this.currentStepIndex];
                if (firstStep?.page) {
                     const currentPath = window.location.pathname;
                     // Handle root path specifically to avoid matching everything
                     // Strict equality check ensures we don't match sub-paths (e.g. /knowledgebase vs /knowledgebase/explore)
                     const isCurrentPage = currentPath === firstStep.page;

                     if (!isCurrentPage) {
                        this.setPendingResume(tourId, depth, this.currentStepIndex);
                        window.location.href = firstStep.page;
                        return;
                     }
                }

                this.startDriverAtStep(this.currentStepIndex);
            } catch (error) {
                console.error('[GuidedTour] Failed to start tour:', error);
                const el = document.createElement('div');
                el.id = 'tour-error';
                el.innerText = error.toString();
                document.body.appendChild(el);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Lazy-load the Driver.js library.
         */
        async ensureDriverLoaded() {
            if (this.driverLoaded) return;

            const [{ driver }] = await Promise.all([
                import('driver.js'),
                import('driver.js/dist/driver.css'),
            ]);

            this._driverFactory = driver;
            this.driverLoaded = true;
        },

        /**
         * Build Driver.js steps and launch from a specific index.
         */
        startDriverAtStep(fromIndex) {
            if (this.driverInstance) {
                this.driverInstance.destroy();
            }

            const driverSteps = this.buildDriverSteps();

            this.driverInstance = this._driverFactory({
                showProgress: true,
                animate: true,
                allowClose: true,
                overlayColor: 'rgba(0, 0, 0, 0.6)',
                stagePadding: 8,
                stageRadius: 8,
                popoverClass: 'guided-tour-popover',
                progressText: this.labels.progress,
                nextBtnText: this.isLastStep(fromIndex) ? this.labels.finish : this.labels.next,
                prevBtnText: this.labels.prev,
                doneBtnText: this.labels.done,

                onNextClick: (element, step, opts) => {
                    const nextIndex = this.currentStepIndex + 1;

                    if (nextIndex >= this.activeSteps.length) {
                        // Tour complete
                        this.completeTour();
                        this.driverInstance.destroy();
                        return;
                    }

                    const nextStep = this.activeSteps[nextIndex];

                    // Check if next step requires navigation
                    if (nextStep?.page) {
                         const currentPath = window.location.pathname;
                         // Handle page navigation strictly
                         const isCurrentPage = currentPath === nextStep.page;

                         if (!isCurrentPage) {
                            this.saveProgress(nextIndex);
                            this.setPendingResume(this.activeTourId, this.activeDepth, nextIndex);
                            this.driverInstance.destroy();
                            window.location.href = nextStep.page;
                            return;
                         }
                    }

                    this.currentStepIndex = nextIndex;
                    this.saveProgress(nextIndex);
                    
                    // Analytics: Track view of next step
                    this.trackEvent('viewed', nextIndex);

                    // Update button text for last step
                    this.driverInstance.moveNext();
                },

                onPrevClick: () => {
                    if (this.currentStepIndex <= 0) return;

                    const prevIndex = this.currentStepIndex - 1;
                    const prevStep = this.activeSteps[prevIndex];

                    // Check if previous step is on a different page
                    if (prevStep?.page) {
                         const currentPath = window.location.pathname;
                         // Handle page navigation strictly
                         const isCurrentPage = currentPath === prevStep.page;

                         if (!isCurrentPage) {
                            this.saveProgress(prevIndex);
                            this.setPendingResume(this.activeTourId, this.activeDepth, prevIndex);
                            this.driverInstance.destroy();
                            window.location.href = prevStep.page;
                            return;
                         }
                    }

                    this.currentStepIndex = prevIndex;
                    this.saveProgress(prevIndex);
                    
                    // Analytics: Track view of prev step
                    this.trackEvent('viewed', prevIndex);
                    
                    this.driverInstance.movePrevious();
                },

                onCloseClick: () => {
                    // Analytics: Track drop off
                    this.trackEvent('dropped_off');

                    this.saveProgress(this.currentStepIndex);
                    this.driverInstance.destroy();
                    this.activeTourId = null;
                },

                onDestroyStarted: () => {
                    // Cleanup when Driver.js is destroyed externally
                },

                steps: driverSteps,
            });

            this.driverInstance.drive(fromIndex);
            
            // Analytics: Track view of initial step
            this.trackEvent('viewed', fromIndex);
        },

        /**
         * Convert our step config into Driver.js step format.
         */
        buildDriverSteps() {
            return this.activeSteps.map((step, index) => {
                let description = step.description;

                // Inject checkpoint buttons if defined
                if (step.checkpoint) {
                    const buttonsHtml = `
                        <div class="mt-4 flex flex-col gap-2 border-t pt-3">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                ${step.checkpoint.question || 'How would you like to proceed?'}
                            </p>
                            <div class="flex gap-2">
                                ${step.checkpoint.actions.map(action => `
                                    <button 
                                        onclick="window.guidedTourCheckpoint('${action.type}', '${action.target || ''}')"
                                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500
                                        ${action.primary 
                                            ? 'bg-primary-600 text-white hover:bg-primary-700' 
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'
                                        }"
                                    >
                                        ${action.label}
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    `;
                    description += buttonsHtml;
                }

                const driverStep = {
                    popover: {
                        title: step.title,
                        description: description,
                        side: step.position || 'bottom',
                        align: 'center',
                    },
                };

                if (step.element) {
                    driverStep.element = step.element;
                }

                return driverStep;
            });
        }, 
        
        /**
         * Handle a checkpoint action triggered from the popover HTML.
         */
        async handleCheckpointAction(type, target) {
            // Analytics: Track choice
            this.trackEvent('checkpoint_choice', this.currentStepIndex, { choice: type, target: target });

            if (this.driverInstance) {
                this.driverInstance.destroy(); // Briefly destroy to allow state change
            }

            if (type === 'switch_depth') {
                // Switch depth (e.g. from high-level to detailed)
                await this.resetTourProgress(this.activeTourId, target);
                // Restart with new depth
                this.startTour(this.activeTourId, target);
                
                if (window.showToast) {
                    window.showToast(`Switched to ${target === 'detailed' ? 'detailed' : 'high-level'} mode`, 'success');
                }
            } else if (type === 'start_tour') {
                // Switch to a completely different tour
                await this.completeTour(); // Mark current as done
                // Start the new tour
                this.startTour(target);
            } else if (type === 'skip_to_end') {
                this.completeTour();
            } else if (type === 'next_step') {
                // Standard next step behavior but triggered manually
                this.currentStepIndex++;
                if (this.currentStepIndex >= this.activeSteps.length) {
                     this.completeTour();
                } else {
                    this.startDriverAtStep(this.currentStepIndex);
                }
            }
        },

        /**
         * Check if a step index is the last step.
         */
        isLastStep(index) {
            return index >= this.activeSteps.length - 1;
        },

        /**
         * Save step progress to the server.
         */
        async saveProgress(stepIndex, isCompleted = false) {
            if (!this.activeTourId) return;

            try {
                await fetch(`/tours/${this.activeTourId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        current_step_index: stepIndex,
                        is_completed: isCompleted,
                        depth: this.activeDepth,
                    }),
                });
            } catch (error) {
                console.error('[GuidedTour] Failed to save progress:', error);
            }
        },

        /**
         * Analytics tracking for tour events.
         */
        async trackEvent(action, stepIndex = null, meta = null) {
            if (!this.activeTourId) return;

            const effectiveStepIndex = stepIndex !== null ? stepIndex : this.currentStepIndex;

            // Calculate time spent since last step change
            let timeSpent = 0;
            if (this.stepStartTime) {
                timeSpent = Math.round((Date.now() - this.stepStartTime) / 1000);
            }
            
            // Reset timer for next step
            this.stepStartTime = Date.now();

            try {
                await fetch(`/tours/${this.activeTourId}/track`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        action: action,
                        step_index: effectiveStepIndex.toString(),
                        depth: this.activeDepth,
                        time_spent_seconds: timeSpent,
                        meta: meta,
                    }),
                });
            } catch (error) {
                // Analytics failures should not block the user
                console.warn('[GuidedTour] Analytics error:', error);
            }
        },

        /**
         * Mark the active tour as completed.
         */
        async completeTour() {
            // Analytics first
            this.trackEvent('completed');

            await this.saveProgress(this.activeSteps.length - 1, true);
            this.activeTourId = null;
            this.activeSteps = [];
            sessionStorage.removeItem(this.storagePrefix + 'pending');

            if (window.showToast) {
                window.showToast('Tour completed! 🎉', 'success');
            }
        },

        /**
         * Dismiss (skip) a tour.
         */
        async dismissTour(tourId) {
            try {
                await fetch(`/tours/${tourId}/dismiss`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });

                // Analytics
                if (this.activeTourId === tourId) {
                    this.trackEvent('dismissed');
                } else {
                     // If dismissing from the list without opening, we can't track as easily via 'trackEvent'
                     // because activeTourId might be null. But let's leave it for now.
                }

                // Update local state
                if (this.availableTours[tourId]) {
                    this.availableTours[tourId].progress = {
                        ...this.availableTours[tourId].progress,
                        is_completed: true,
                    };
                }

                if (this.activeTourId === tourId) {
                    if (this.driverInstance) {
                        this.driverInstance.destroy();
                    }
                    this.activeTourId = null;
                }
                
                // Show success message and reload to hide the button
                if (window.showToast) {
                    window.showToast('Tour marked as complete!', 'success');
                }
                
                // Reload the page to update the UI
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } catch (error) {
                console.error('[GuidedTour] Failed to dismiss tour:', error);
                if (window.showToast) {
                    window.showToast('Failed to mark tour as complete. Please try again.', 'error');
                }
            }
        },

        /**
         * Reset a tour to allow replaying.
         */
        async resetTourProgress(tourId, depth = 'high-level') {
            try {
                await fetch(`/tours/${tourId}/reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ depth }),
                });
            } catch (error) {
                console.error('[GuidedTour] Failed to reset tour:', error);
            }
        },

        /**
         * Store pending resume data in sessionStorage for multi-page navigation.
         */
        setPendingResume(tourId, depth, stepIndex) {
            sessionStorage.setItem(
                this.storagePrefix + 'pending',
                JSON.stringify({ tourId, depth, stepIndex })
            );
        },

        /**
         * Toggle the tour picker modal.
         */
        togglePicker() {
            this.showPicker = !this.showPicker;
            if (this.showPicker) {
                this.loadAvailableTours();
            }
        },

        /**
         * Get incomplete tours for the current user.
         */
        get incompleteTours() {
            return Object.values(this.availableTours).filter(
                tour => !tour.progress?.is_completed
            );
        },

        /**
         * Get completed tours for the current user.
         */
        get completedTours() {
            return Object.values(this.availableTours).filter(
                tour => tour.progress?.is_completed
            );
        },

        /**
         * Whether there are any available tours to show.
         */
        get hasTours() {
            return Object.keys(this.availableTours).length > 0;
        },

        /**
         * Get the CSRF token from the meta tag.
         */
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },
    };
}
