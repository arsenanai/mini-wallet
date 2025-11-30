import en from '@/locales/en.json';
import { config, DOMWrapper } from '@vue/test-utils';
import { vi } from 'vitest'; // Make sure vi is imported
import { createI18n, I18n } from 'vue-i18n';

// This i18n instance will be used for all tests.
const i18n: I18n = createI18n({
    legacy: false, // Must be false for Composition API
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en },
});

// Install plugins and mocks globally for all tests.
config.global.plugins = [i18n];

// --- Mock Ziggy ---
// This is the missing piece. We need to simulate the Ziggy object
// that Laravel's @routes directive would normally create.
const ziggyMock = {
    routes: {
        'api.transactions.store': {
            uri: 'api/transactions',
            methods: ['POST'],
        },
        dashboard: {
            uri: 'dashboard',
            methods: ['GET', 'HEAD'],
        },
        'profile.edit': { uri: 'profile', methods: ['GET'] }, // For AuthenticatedLayout
        logout: { uri: 'logout', methods: ['POST'] }, // For AuthenticatedLayout
    },
    url: 'http://localhost',
};

// Make the Ziggy config and a more complete route() function mock available globally.
vi.stubGlobal('Ziggy', ziggyMock);

const routeMock = (name?: string) => {
    if (!name) {
        // Handles route().current('...')
        return {
            current: (routeName: string) =>
                ziggyMock.routes[routeName] !== undefined,
        };
    }
    return `http://localhost/${ziggyMock.routes[name]?.uri ?? name}`;
};
config.global.mocks.route = routeMock;

// This is a workaround for a potential Vitest/JSDOM issue.
config.global.components = { DOMWrapper };
