import { mount } from '@vue/test-utils';
import Dashboard from '@/Pages/Dashboard.vue';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { Paginated, Transaction, User } from '@/types';

// --- Mock Data ---
const currentUser: User = {
    id: 1,
    name: 'Current User',
    email: 'current@user.com',
};
const otherUser: User = {
    id: 2,
    name: 'Other User',
    email: 'other@user.com',
};

const initialTransaction: Transaction = {
    id: 1,
    reference_id: 'initial-ref',
    amount: 50,
    commission_fee: 0,
    type: 'deposit',
    status: 'completed',
    created_at: '2024-01-01T10:00:00.000000Z',
    sender: otherUser,
    receiver: currentUser,
};

const initialProps = {
    balance: 1000,
    transactions: {
        data: [initialTransaction],
        links: { first: '', last: '', prev: null, next: null },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            path: '',
            per_page: 15,
            to: 1,
            total: 1,
        },
    } as Paginated<Transaction>,
};

// --- Mocks ---

// Mock Inertia's usePage()
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original: any = await importOriginal();
    return {
        ...original,
        usePage: () => ({
            props: {
                auth: { user: currentUser },
            },
        }),
        Head: { template: '<div />' }, // Stub the Head component
        Link: { template: '<a />' }, // Stub the Link component
    };
});

// Mock the global route() helper, as it's used in AuthenticatedLayout
vi.stubGlobal('route', (name: string) => `http://localhost/${name}`);

// Mock Laravel Echo and its methods
const mockListen = vi.fn();
const mockPrivate = vi.fn(() => ({ listen: mockListen }));
window.Echo = {
    private: mockPrivate,
    leave: vi.fn(),
} as any;

describe('Dashboard.vue', () => {
    beforeEach(() => {
        // Clear mocks before each test
        mockListen.mockClear();
        mockPrivate.mockClear();
    });

    it('updates balance and prepends a new transaction when a TransactionCompleted event is received', async () => {
        const wrapper = mount(Dashboard, {
            props: initialProps,
            // Provide a more complete mock for the route() helper to handle
            // both route('name') and route().current('name') calls.
            global: {
                mocks: {
                    route: (name?: string) => {
                        if (name) {
                            return `http://localhost/${name}`;
                        }
                        // Mock the `current()` method for active link checking
                        return { current: () => false };
                    },
                    $page: {
                        props: { auth: { user: currentUser } },
                    },
                },
            },
        });

        // 1. Verify initial state
        expect(wrapper.text()).toContain('$1,000.00');
        expect(wrapper.text()).toContain('Received from Other User');
        expect(wrapper.findAll('[data-testid="transaction-item"]').length).toBe(1);

        // 2. Simulate the Pusher event
        const newTransactionEvent: { balance: number; transaction: Transaction } = {
            balance: 950.5,
            transaction: {
                id: 2,
                reference_id: 'new-ref',
                amount: 48,
                commission_fee: 1.5,
                type: 'transfer',
                status: 'completed',
                created_at: '2024-01-16T12:00:00.000000Z',
                sender: currentUser,
                receiver: otherUser,
            },
        };

        // Get the callback passed to `listen` and execute it
        const listenCallback = mockListen.mock.calls[0][1];
        listenCallback(newTransactionEvent);

        await wrapper.vm.$nextTick();

        // 3. Assert the UI has updated
        expect(wrapper.text()).toContain('$950.50'); // Balance is updated
        expect(wrapper.text()).toContain('Sent to Other User'); // New transaction text
        expect(wrapper.findAll('[data-testid="transaction-item"]').length).toBe(2); // New transaction is added
        expect(wrapper.findAll('[data-testid="transaction-item"]')[0].text()).toContain('Sent to Other User'); // New transaction is at the top
    });
});