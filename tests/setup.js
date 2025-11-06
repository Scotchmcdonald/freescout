// Test setup file for Vitest
import { beforeEach, vi } from 'vitest';

// Mock window.Echo for tests
global.Echo = {
    private: vi.fn().mockReturnThis(),
    channel: vi.fn().mockReturnThis(),
    listen: vi.fn().mockReturnThis(),
    leave: vi.fn(),
};

// Mock Laravel-specific globals
global.Laravel = {
    csrfToken: 'test-csrf-token',
};

// Mock route helper
global.route = vi.fn((name, params) => {
    const routes = {
        'conversations.show': (id) => `/conversation/${id}`,
        'conversations.reply': (id) => `/conversation/${id}/reply`,
        'conversations.upload': () => '/conversations/upload',
    };
    
    if (typeof routes[name] === 'function') {
        return routes[name](params);
    }
    
    return `/${name}`;
});

// Mock fetch globally
global.fetch = vi.fn();

// Reset mocks before each test
beforeEach(() => {
    vi.clearAllMocks();
});
