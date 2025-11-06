import { describe, it, expect, beforeEach, vi } from 'vitest';
import { RealtimeNotifications } from '../../resources/js/notifications.js';

describe('RealtimeNotifications', () => {
    let notificationManager;

    beforeEach(() => {
        // Set up a mock DOM element
        document.body.innerHTML = `
            <meta name="user-id" content="1">
            <meta name="conversation-id" content="100">
        `;
        
        notificationManager = new RealtimeNotifications();
    });

    describe('Initialization', () => {
        it('should initialize with user ID from meta tag', () => {
            expect(notificationManager.userId).toBe('1');
        });

        it('should initialize with conversation ID from meta tag', () => {
            expect(notificationManager.conversationId).toBe('100');
        });

        it('should create notification container', () => {
            const container = document.getElementById('notification-container');
            expect(container).toBeTruthy();
            expect(container.classList.contains('fixed')).toBe(true);
        });
    });

    describe('setupContainer', () => {
        it('should not create duplicate containers', () => {
            // Create another instance
            new RealtimeNotifications();
            
            const containers = document.querySelectorAll('#notification-container');
            expect(containers.length).toBe(1);
        });
    });

    describe('init', () => {
        it('should not initialize if userId is missing', () => {
            document.querySelector('meta[name="user-id"]').remove();
            const notifications = new RealtimeNotifications();
            
            // Should not throw error
            expect(() => notifications.init()).not.toThrow();
        });

        it('should set up Echo listeners when Echo is available', () => {
            notificationManager.init();
            
            expect(global.Echo.private).toHaveBeenCalledWith('user.1');
        });
    });
});
