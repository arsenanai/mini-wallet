import Dashboard from '@/Pages/Dashboard.vue';
import { Paginated, Transaction, User } from '@/types';
import { VueWrapper, flushPromises, mount } from '@vue/test-utils';
import { AxiosStatic } from 'axios';
import { vi } from 'vitest';

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
    id: 'uuid-initial',
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
    balance: '1000.00',
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

// Mock axios
const mockAxiosGet = vi.fn();
// We cast to `unknown` first and then to `AxiosStatic` to satisfy TypeScript
// when we are creating a partial mock of the axios object.
window.axios = {
    ...window.axios,
    get: mockAxiosGet,
} as unknown as AxiosStatic;

// Mock Laravel Echo and Pusher
const mockListen = vi.fn();
window.Echo = {
    private: vi.fn(() => ({
        listen: mockListen,
        on: vi.fn(), // Mock the 'on' method as well
    })),
    leave: vi.fn(),
} as any;

// Mock the global `route` function from Ziggy
const route = vi.fn(
    (name, params) => `http://localhost/api/transactions/${params.transaction}`,
);
vi.stubGlobal('route', route);

// Mock the vue-i18n composable at the module level
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: mock$t,
    }),
}));

const mockTranslations = {
    'dashboard.sent_to': 'Sent to {name}',
    'dashboard.received_from': 'Received from {name}',
    'dashboard.header': 'Mini Wallet Dashboard',
    'dashboard.balance': 'Current Balance',
    'dashboard.transaction_history': 'Transaction History',
    'dashboard.send_money': 'Send Money',
    'dashboard.recipient_email': "Recipient's Email",
    'dashboard.amount': 'Amount',
    'dashboard.transfer_successful': 'Transfer successful!',
    'dashboard.no_transactions': 'No transactions yet.',
};

const mock$t = (key: string, values?: Record<string, string>) => {
    let translation =
        mockTranslations[key as keyof typeof mockTranslations] || key;
    if (values) {
        Object.keys(values).forEach((valueKey) => {
            translation = translation.replace(
                `{${valueKey}}`,
                values[valueKey],
            );
        });
    }
    return translation;
};

// Helper to mount the component with all necessary mocks
const mountComponent = (props: any): VueWrapper => {
    return mount(Dashboard, {
        props: JSON.parse(JSON.stringify(props)),
        global: {
            mocks: {
                $t: mock$t,
                usePage: () => ({ props: { auth: { user: currentUser } } }),
            },
        },
    });
};

beforeEach(() => {
    mockListen.mockClear();
    mockAxiosGet.mockClear();
});

describe('Dashboard.vue', () => {
    it('updates balance and prepends a new transaction when a TransactionCompleted event is received', async () => {
        const newTransactionEvent: {
            balance: string; // Simulate real-world data where numbers are often strings
            transaction: Transaction;
        } = {
            balance: '950.5000',
            transaction: {
                id: 'uuid-new',
                reference_id: 'new-ref',
                amount: 48.0,
                commission_fee: 1.5, // This will be a number from the event, but let's keep it for now
                type: 'transfer',
                status: 'completed',
                created_at: '2024-01-16T12:00:00.000000Z',
                sender: currentUser,
                receiver: otherUser,
            },
        };

        const wrapper = mountComponent(initialProps);
        await flushPromises(); // Wait for onMounted to complete and Echo listener to be set up

        // 1. Verify initial state
        expect(wrapper.text()).toContain('$1,000.00');
        expect(wrapper.text()).toContain('Received from Other User');
        expect(wrapper.findAll('[data-testid="transaction-item"]').length).toBe(
            1,
        );

        // 2. Simulate the Pusher event
        // Get the callback passed to `listen` and execute it
        const listenCallback = mockListen.mock.calls[0][1];
        listenCallback(newTransactionEvent);

        await wrapper.vm.$nextTick();

        // 3. Assert the UI has updated
        expect(wrapper.text()).toContain('$950.50'); // Balance is updated
        expect(wrapper.text()).toContain('Sent to Other User'); // New transaction text
        expect(wrapper.findAll('[data-testid="transaction-item"]').length).toBe(
            2,
        ); // New transaction is added
        expect(
            wrapper.findAll('[data-testid="transaction-item"]')[0].text(),
        ).toContain('Sent to Other User'); // New transaction is at the top
    });

    it('fetches full transaction details when the event payload is incomplete', async () => {
        // 1. Arrange
        // This event payload simulates a broadcast that doesn't include the sender/receiver objects.
        const incompleteTransactionEvent = {
            balance: '950.5000',
            transaction: {
                id: 'uuid-incomplete',
                reference_id: 'incomplete-ref',
                amount: 48.0,
                commission_fee: 1.5,
                type: 'transfer',
                status: 'completed',
                created_at: '2024-01-16T12:00:00.000000Z',
                // sender and receiver objects are missing
            } as unknown as Transaction,
        };

        // This is the full data we expect axios to return.
        const fullTransaction: Transaction = {
            ...incompleteTransactionEvent.transaction,
            sender: currentUser,
            receiver: otherUser,
        };

        // Mock the axios response
        mockAxiosGet.mockResolvedValue({ data: { data: fullTransaction } });

        const wrapper = mountComponent(initialProps);
        await flushPromises(); // Wait for onMounted to complete

        // 2. Act
        // Trigger the event with the incomplete data
        const listenCallback = mockListen.mock.calls[0][1];
        await listenCallback(incompleteTransactionEvent);
        await flushPromises(); // Allow the axios call to resolve
        await wrapper.vm.$nextTick(); // Allow the DOM to update

        // 3. Assert
        // Assert that axios was called to fetch the full details
        expect(mockAxiosGet).toHaveBeenCalledOnce();
        expect(mockAxiosGet).toHaveBeenCalledWith(
            route('api.transactions.show', {
                transaction: incompleteTransactionEvent.transaction.id,
            }),
        );

        // Assert that the UI updated with the *full* transaction data
        expect(wrapper.text()).toContain('$950.50'); // Balance updated
        const transactionItems = wrapper.findAll(
            '[data-testid="transaction-item"]',
        );

        expect(transactionItems.length).toBe(2);
        expect(transactionItems[0].text()).toContain('Sent to Other User'); // Correct text requires the full object
    });
});
