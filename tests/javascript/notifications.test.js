/**
 * @jest-environment jsdom
 */

describe('Notifications', () => {
    let mockNotificationCallback;
    let mockListenReturn;

    beforeEach(() => {
        // Reset mocks before each test
        mockNotificationCallback = jest.fn();
        
        // Create a mock that properly chains .notification()
        mockListenReturn = {
            notification: jest.fn().mockReturnThis()
        };

        // Mock window.Echo with proper chaining
        global.window.Echo = {
            private: jest.fn().mockReturnValue({
                listen: jest.fn().mockReturnValue(mockListenReturn)
            })
        };
    });

    afterEach(() => {
        // Clean up
        delete global.window.Echo;
        jest.clearAllMocks();
    });

    test('Echo.private().listen().notification() chains correctly', () => {
        // Test that the chain works
        const result = window.Echo
            .private('test-channel')
            .listen('TestEvent', () => {})
            .notification((notification) => {
                mockNotificationCallback(notification);
            });

        // Verify the chain was called correctly
        expect(window.Echo.private).toHaveBeenCalledWith('test-channel');
        expect(window.Echo.private().listen).toHaveBeenCalledWith('TestEvent', expect.any(Function));
        expect(mockListenReturn.notification).toHaveBeenCalledWith(expect.any(Function));
        
        // Verify notification returns the mock object for further chaining
        expect(result).toBe(mockListenReturn);
    });

    test('notification callback is called with notification data', () => {
        const testNotification = { id: 1, message: 'Test notification' };
        
        // Setup the notification mock to call the callback
        mockListenReturn.notification = jest.fn((callback) => {
            callback(testNotification);
            return mockListenReturn;
        });

        window.Echo
            .private('test-channel')
            .listen('TestEvent', () => {})
            .notification((notification) => {
                expect(notification).toEqual(testNotification);
            });

        expect(mockListenReturn.notification).toHaveBeenCalled();
    });

    test('multiple listeners can be chained', () => {
        const listener1 = jest.fn();
        const listener2 = jest.fn();

        window.Echo
            .private('test-channel')
            .listen('Event1', listener1)
            .notification(listener2);

        expect(window.Echo.private).toHaveBeenCalledWith('test-channel');
        expect(mockListenReturn.notification).toHaveBeenCalledWith(listener2);
    });
});
