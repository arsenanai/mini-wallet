import TransferForm from '@/Components/TransferForm.vue';
import { VueWrapper, flushPromises, mount } from '@vue/test-utils';
import { AxiosError, type AxiosStatic } from 'axios';
import { afterEach, describe, expect, it, vi } from 'vitest';

// Mock axios
const mockAxiosPost = vi.fn();
window.axios = {
    ...window.axios,
    post: mockAxiosPost,
} as unknown as AxiosStatic;

vi.mock('ziggy-js');

// Mock the global `route` function from Ziggy
const route = vi.fn(() => 'http://localhost/api/transactions');
vi.stubGlobal('route', route);

// Helper to mount the component with all necessary mocks
const mountComponent = (): VueWrapper => {
    return mount(TransferForm, {
        global: {
            // Mock i18n and useI18n
            mocks: {
                $t: (key: string) => key,
                useI18n: () => ({ t: (key: string) => key }),
            },
        },
    });
};

describe('TransferForm.vue', () => {
    afterEach(() => {
        mockAxiosPost.mockClear();
        // Clear any fake timers between tests
        vi.useRealTimers();
    });

    it('displays validation errors for email and amount fields', async () => {
        // 1. Mock a realistic AxiosError for a 422 validation failure.
        const error = new AxiosError('Validation Error');
        error.response = {
            data: {
                errors: {
                    receiver_email: [
                        'The selected recipient email is invalid.',
                    ],
                    amount: ['The amount must be at least 0.01.'],
                },
            },
            status: 422,
            statusText: 'Unprocessable Content',
            headers: {},
            config: {} as any,
        };
        mockAxiosPost.mockRejectedValue(error);

        const wrapper = mountComponent();

        // 2. Trigger form submission
        await wrapper.find('form').trigger('submit.prevent');

        // 3. Wait for the rejected promise to be processed and the DOM to update
        await flushPromises();
        await wrapper.vm.$nextTick();

        // 4. Assert the errors are visible
        const emailError = wrapper.find('[data-testid="receiver-email-error"]');
        const amountError = wrapper.find('[data-testid="amount-error"]');

        expect(emailError.exists()).toBe(true);
        expect(emailError.text()).toBe(
            'The selected recipient email is invalid.',
        );
        expect(amountError.exists()).toBe(true);
        expect(amountError.text()).toBe('The amount must be at least 0.01.');
    });

    it('disables the submit button while the form is processing', async () => {
        // 1. Mock axios to return a promise that never resolves, keeping it in a processing state
        mockAxiosPost.mockReturnValue(new Promise(() => {}));

        const wrapper = mountComponent();

        // 2. Trigger form submission
        await wrapper.find('form').trigger('submit.prevent');
        await wrapper.vm.$nextTick();

        // 4. Assert the button is disabled
        const button = wrapper.find('button[type="submit"]');
        expect((button.element as HTMLButtonElement).disabled).toBe(true);
    });

    it('resets the form and shows a success message upon a successful API response', async () => {
        // 1. Mock a successful response from axios
        vi.useFakeTimers();
        mockAxiosPost.mockResolvedValue({
            data: { balance: '900.00', transaction: {} },
        });

        const wrapper = mountComponent();

        // 2. Trigger form submission
        await wrapper.find('form').trigger('submit.prevent');

        // 3. Wait for the promise to resolve and the DOM to update
        await flushPromises();
        await wrapper.vm.$nextTick();

        // 4. Assert the success message is visible
        const successMessage = wrapper.find('[data-testid="success-message"]');
        expect(successMessage.exists()).toBe(true); // The element is now in the DOM
        expect(successMessage.text()).toBe('dashboard.transfer_successful'); // It uses the i18n key

        // 5. Fast-forward time to check if the success message disappears
        await vi.advanceTimersByTimeAsync(2000);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('[data-testid="success-message"]').exists()).toBe(
            false,
        );
    });
});
