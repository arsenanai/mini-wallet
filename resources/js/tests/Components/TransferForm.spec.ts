import TransferForm from '@/Components/TransferForm.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock axios
const mockAxiosPost = vi.fn();
window.axios = {
    post: mockAxiosPost,
} as any;

describe('TransferForm.vue', () => {
    beforeEach(() => {
        mockAxiosPost.mockClear();
        vi.useFakeTimers();
    });

    it('displays validation errors for email and amount fields', async () => {
        mockAxiosPost.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        recipient_email: ['The recipient email is invalid.'],
                        amount: ['The amount must be a positive number.'],
                    },
                },
            },
        });
        const wrapper = mount(TransferForm, {
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        await wrapper.find('form').trigger('submit.prevent');
        await wrapper.vm.$nextTick();

        const text = wrapper.text();
        expect(text).toContain('The recipient email is invalid.');
        expect(text).toContain('The amount must be a positive number.');
        expect(mockAxiosPost).toHaveBeenCalledTimes(1);
    });

    it('disables the submit button while the form is processing', async () => {
        // Make the promise never resolve to keep it in a processing state
        mockAxiosPost.mockReturnValue(new Promise(() => {}));

        const wrapper = mount(TransferForm, {
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        await wrapper.find('form').trigger('submit.prevent');
        await wrapper.vm.$nextTick();

        const button = wrapper.find('button[type="submit"]');
        expect(button.attributes('disabled')).toBeDefined();
        expect(mockAxiosPost).toHaveBeenCalledTimes(1);
    });

    it('resets the form and shows a success message upon a successful API response', async () => {
        mockAxiosPost.mockResolvedValue({ status: 201, data: {} });

        const wrapper = mount(TransferForm, {
            global: {
                mocks: {
                    $t: (key: string) => {
                        // Provide a specific translation for the success message
                        if (key === 'dashboard.transfer_successful') {
                            return 'Transfer successful!';
                        }
                        return key; // Return other keys as-is
                    },
                },
            },
        });

        const emailInput = wrapper.find<HTMLInputElement>(
            'input[type="email"]',
        );
        const amountInput = wrapper.find<HTMLInputElement>(
            'input[type="number"]',
        );

        await emailInput.setValue('test@test.com');
        await amountInput.setValue('100');

        // Find and submit the form
        await wrapper.find('form').trigger('submit.prevent');

        await wrapper.vm.$nextTick();

        // Check that the success message is visible
        expect(wrapper.text()).toContain('Transfer successful!');

        // Check that the form fields were reset
        expect(emailInput.element.value).toBe('');
        expect(amountInput.element.value).toBe('');

        // Fast-forward time to check if the success message disappears
        await vi.advanceTimersByTimeAsync(2000);
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).not.toContain('Transfer successful!');
    });
});
