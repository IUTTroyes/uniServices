import { vi } from 'vitest';

// Mock global localStorage
global.localStorage = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn(),
};

// Mock global window.location
Object.defineProperty(global, 'window', {
    value: {
        location: {
            replace: vi.fn(),
        },
    },
    writable: true,
});
