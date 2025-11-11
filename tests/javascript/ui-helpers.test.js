import { describe, it, expect, beforeEach, vi } from 'vitest';
import Swal from 'sweetalert2';

// Mock SweetAlert2
vi.mock('sweetalert2', () => ({
    default: {
        fire: vi.fn().mockResolvedValue({ isConfirmed: true }),
        showLoading: vi.fn(),
        close: vi.fn(),
        stopTimer: vi.fn(),
        resumeTimer: vi.fn(),
        mixin: vi.fn(() => ({
            fire: vi.fn().mockResolvedValue({})
        }))
    }
}));

import { UIHelpers } from '../../resources/js/ui-helpers.js';

describe('UI Helpers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('showToast', () => {
        it('should create and show a toast notification', () => {
            UIHelpers.showToast('Toast message', 'success');
            
            expect(Swal.mixin).toHaveBeenCalledWith(
                expect.objectContaining({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                })
            );
        });

        it('should use custom duration when provided', () => {
            UIHelpers.showToast('Message', 'info', 5000);
            
            expect(Swal.mixin).toHaveBeenCalledWith(
                expect.objectContaining({
                    timer: 5000
                })
            );
        });
    });

    describe('confirm', () => {
        it('should call Swal.fire with confirmation options', async () => {
            await UIHelpers.confirm('Are you sure?', 'This action cannot be undone');
            
            expect(Swal.fire).toHaveBeenCalledWith({
                title: 'Are you sure?',
                text: 'This action cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: expect.any(String),
                cancelButtonColor: expect.any(String),
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            });
        });

        it('should return true when confirmed', async () => {
            Swal.fire.mockResolvedValueOnce({ isConfirmed: true });
            
            const result = await UIHelpers.confirm('Confirm?', 'Message');
            
            expect(result).toBe(true);
        });

        it('should return false when cancelled', async () => {
            Swal.fire.mockResolvedValueOnce({ isConfirmed: false });
            
            const result = await UIHelpers.confirm('Confirm?', 'Message');
            
            expect(result).toBe(false);
        });

        it('should support custom button text', async () => {
            await UIHelpers.confirm('Delete?', 'Are you sure?', 'Delete', 'Keep');
            
            expect(Swal.fire).toHaveBeenCalledWith(
                expect.objectContaining({
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Keep'
                })
            );
        });
    });

    describe('showLoading', () => {
        it('should call Swal.fire with loading state', () => {
            UIHelpers.showLoading('Processing...');
            
            expect(Swal.fire).toHaveBeenCalledWith({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: expect.any(Function)
            });
        });

        it('should show loading spinner on open', () => {
            UIHelpers.showLoading('Loading');
            
            // Get the didOpen callback and call it
            const call = Swal.fire.mock.calls[0][0];
            call.didOpen();
            
            expect(Swal.showLoading).toHaveBeenCalled();
        });

        it('should default to "Processing..." message', () => {
            UIHelpers.showLoading();
            
            expect(Swal.fire).toHaveBeenCalledWith(
                expect.objectContaining({
                    title: 'Processing...'
                })
            );
        });
    });

    describe('hideLoading', () => {
        it('should close the Swal modal', () => {
            UIHelpers.hideLoading();
            
            expect(Swal.close).toHaveBeenCalled();
        });
    });

    describe('formatDateTime', () => {
        it('should format date in short format', () => {
            const date = new Date('2024-01-15T10:30:00');
            const formatted = UIHelpers.formatDateTime(date, 'short');
            
            expect(formatted).toMatch(/Jan 15/);
        });

        it('should format date in long format', () => {
            const date = new Date('2024-01-15T10:30:00');
            const formatted = UIHelpers.formatDateTime(date, 'long');
            
            expect(formatted).toMatch(/January 15, 2024/);
        });

        it('should handle string dates', () => {
            const formatted = UIHelpers.formatDateTime('2024-01-15T10:30:00');
            
            expect(formatted).toBeTruthy();
        });
    });

    describe('debounce', () => {
        it('should debounce function calls', async () => {
            vi.useFakeTimers();
            const mockFn = vi.fn();
            const debounced = UIHelpers.debounce(mockFn, 300);
            
            debounced('test1');
            debounced('test2');
            debounced('test3');
            
            expect(mockFn).not.toHaveBeenCalled();
            
            vi.advanceTimersByTime(300);
            
            expect(mockFn).toHaveBeenCalledTimes(1);
            expect(mockFn).toHaveBeenCalledWith('test3');
            
            vi.useRealTimers();
        });
    });

    describe('throttle', () => {
        it('should throttle function calls', () => {
            vi.useFakeTimers();
            const mockFn = vi.fn();
            const throttled = UIHelpers.throttle(mockFn, 300);
            
            throttled('test1');
            throttled('test2');
            throttled('test3');
            
            // Should only be called once immediately
            expect(mockFn).toHaveBeenCalledTimes(1);
            expect(mockFn).toHaveBeenCalledWith('test1');
            
            vi.advanceTimersByTime(300);
            
            // Now can be called again
            throttled('test4');
            expect(mockFn).toHaveBeenCalledTimes(2);
            expect(mockFn).toHaveBeenCalledWith('test4');
            
            vi.useRealTimers();
        });
    });

    describe('copyToClipboard', () => {
        beforeEach(() => {
            // Mock clipboard API
            Object.defineProperty(navigator, 'clipboard', {
                value: {
                    writeText: vi.fn().mockResolvedValue(undefined)
                },
                writable: true,
                configurable: true
            });
        });

        it('should copy text to clipboard', async () => {
            const text = 'Copy this text';
            
            await UIHelpers.copyToClipboard(text);
            
            expect(navigator.clipboard.writeText).toHaveBeenCalledWith(text);
        });

        it('should return true on success', async () => {
            const result = await UIHelpers.copyToClipboard('text');
            
            expect(result).toBe(true);
        });

        it('should return false on error', async () => {
            navigator.clipboard.writeText.mockRejectedValueOnce(new Error('Failed'));
            
            const result = await UIHelpers.copyToClipboard('text');
            
            expect(result).toBe(false);
        });
    });
});
