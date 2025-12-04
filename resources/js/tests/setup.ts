import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy/dist/index.esm.js';
import { i18n } from '../i18n';

// Mock the global Ziggy object that the ZiggyVue plugin relies on.
// This object is normally created by the `@routes` Blade directive.
const ziggyMock = {
    routes: {
        dashboard: { uri: 'dashboard', methods: ['GET'] },
        'profile.edit': { uri: 'profile', methods: ['GET'] },
        logout: { uri: 'logout', methods: ['POST'] },
        'api.transactions.store': {
            uri: 'api/transactions',
            methods: ['POST'],
        },
        'api.transactions.show': {
            uri: 'api/transactions/{transaction}',
            methods: ['GET'],
        },
    },
    url: 'http://localhost',
    port: null,
    defaults: {},
    location: 'http://localhost/dashboard', // Mock the current location for route().current()
};
vi.stubGlobal('Ziggy', ziggyMock);

// Install plugins globally for all tests, mimicking app.ts
config.global.plugins = [i18n, ZiggyVue];

// Mock Inertia's usePage() to provide a default user for all tests
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...original,
        usePage: () => ({
            props: {
                auth: {
                    user: { id: 1, name: 'Test User', email: 'test@user.com' },
                },
            },
        }),
        // Stub Inertia components used in layouts to prevent rendering issues
        Head: { template: '<div />' },
        Link: { template: '<a />' },
    };
});

// Mock Laravel Echo and Pusher for all tests
window.Echo = {
    private: vi.fn(() => {
        // Return an object that mimics the Echo channel, allowing method chaining.
        const channelMock = {
            on: vi.fn().mockReturnThis(), // .on() returns the channel for chaining
            listen: vi.fn().mockReturnThis(), // .listen() returns the channel for chaining
        };
        return channelMock;
    }),
    leave: vi.fn(),
};

// Partially mock vue-i18n. This keeps the original module's exports (like createI18n)
// while allowing us to provide a mock implementation for useI18n.
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal<typeof import('vue-i18n')>();
    return {
        ...actual,
        useI18n: () => ({
            t: (key: string) => key,
        }),
    };
});
