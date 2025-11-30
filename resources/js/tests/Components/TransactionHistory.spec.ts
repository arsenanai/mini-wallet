import TransactionHistory from '@/Components/TransactionHistory.vue';
import { i18n } from '@/i18n';
import { Paginated, Transaction, User } from '@/types';
import { config, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

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

const mockTransactionsTemplate: Paginated<Transaction> = {
    data: [],
    links: { first: '', last: '', prev: null, next: null },
    meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        path: '',
        links: [],
        per_page: 15,
        to: 1,
        total: 1,
    },
};

// Mock the usePage() composable from Inertia.js
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...original,
        usePage: () => ({
            props: {
                auth: { user: currentUser },
            },
        }),
    };
});

// We still need to spy on the composable-based `t` function for this component
vi.spyOn(i18n.global, 't').mockImplementation(
    config.global.mocks.$t as (key: string | number) => string,
);

describe('TransactionHistory.vue', () => {
    it('correctly formats a date string into a readable format', () => {
        const transactions = JSON.parse(
            JSON.stringify(mockTransactionsTemplate),
        );
        transactions.data.push({
            id: 'uuid-1',
            reference_id: 'test-ref',
            amount: 100,
            commission_fee: 1.5,
            type: 'transfer',
            status: 'completed',
            created_at: '2024-01-15T14:30:00.000000Z',
            sender: currentUser,
            receiver: otherUser,
        });

        const wrapper = mount(TransactionHistory, {
            props: { transactions },
        });

        // Note: The exact output depends on the test runner's timezone.
        // We check for the presence of the core date and time parts.
        expect(wrapper.text()).toContain('January 15, 2024');
    });

    it('displays a "No transactions yet" message when the transaction list is empty', () => {
        const wrapper = mount(TransactionHistory, {
            props: { transactions: mockTransactionsTemplate },
        });

        expect(wrapper.text()).toContain('No transactions yet.');
    });

    it('correctly renders an outgoing transaction', () => {
        const transactions = JSON.parse(
            JSON.stringify(mockTransactionsTemplate),
        );
        transactions.data.push({
            id: 'uuid-outgoing',
            reference_id: 'test-ref-outgoing',
            amount: 100,
            commission_fee: 1.5,
            type: 'transfer',
            status: 'completed',
            created_at: '2024-01-15T14:30:00.000000Z',
            sender: currentUser,
            receiver: otherUser,
        });

        const wrapper = mount(TransactionHistory, {
            props: { transactions },
        });

        expect(wrapper.text()).toContain('Sent to Other User');
        expect(wrapper.text()).toContain('-$101.50'); // Amount + commission
        expect(wrapper.find('.text-red-500').exists()).toBe(true); // Icon color
    });

    it('correctly renders an incoming transaction', () => {
        const transactions = JSON.parse(
            JSON.stringify(mockTransactionsTemplate),
        );
        transactions.data.push({
            id: 'uuid-incoming',
            reference_id: 'test-ref-incoming',
            amount: 50,
            commission_fee: 0, // Commission is on sender side
            type: 'transfer',
            status: 'completed',
            created_at: '2024-01-16T10:00:00.000000Z',
            sender: otherUser,
            receiver: currentUser,
        });

        const wrapper = mount(TransactionHistory, {
            props: { transactions },
        });

        expect(wrapper.text()).toContain('Received from Other User');
        expect(wrapper.text()).toContain('+$50.00');
        expect(wrapper.find('.text-green-500').exists()).toBe(true); // Icon color
    });
});
