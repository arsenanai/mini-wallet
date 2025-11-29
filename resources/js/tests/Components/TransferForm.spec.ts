import { mount } from '@vue/test-utils';
import TransferForm from '@/Components/TransferForm.vue';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';

// A reactive object to simulate the form state from useForm.
// Using `reactive` is more robust for nested objects like `errors`.
const mockFormState = reactive({
    recipient_email: '' as string,
    amount: null as number | null,
    errors: {} as Record<string, string>,
    processing: false,
    recentlySuccessful: false,
    post: vi.fn(),
    reset: vi.fn(),
});

// Mock the useForm() composable from Inertia.js
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original: any = await importOriginal();
    return {
        ...original,
        useForm: () => mockFormState,
    };
});

// Mock the global route() helper
vi.stubGlobal('route', (name: string) => `http://localhost/${name}`);

describe('TransferForm.vue', () => {
    // Reset the mock state before each test
    beforeEach(() => {
        mockFormState.errors = {};
        mockFormState.processing = false;
        mockFormState.recentlySuccessful = false;
        mockFormState.post.mockClear();
        mockFormState.reset.mockClear();
    });

    it('displays validation errors for email and amount fields', async () => {
        const wrapper = mount(TransferForm);

        // Simulate receiving validation errors
        mockFormState.errors = {
            recipient_email: 'The recipient email is invalid.',
            amount: 'The amount must be a positive number.',
        };

        await wrapper.vm.$nextTick();

        const text = wrapper.text();
        expect(text).toContain('The recipient email is invalid.');
        expect(text).toContain('The amount must be a positive number.');
    });

    it('disables the submit button while the form is processing', async () => {
        const wrapper = mount(TransferForm);

        // Simulate form processing
        mockFormState.processing = true;

        await wrapper.vm.$nextTick();

        const button = wrapper.find('button[type="submit"]');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('resets the form and shows a success message upon a successful API response', async () => {
        // Mock the form.post to immediately call onSuccess
        mockFormState.post.mockImplementation((_url, options) => {
            if (options.onSuccess) {
                options.onSuccess();
            }
            mockFormState.recentlySuccessful = true;
        });

        const wrapper = mount(TransferForm);

        // Find and submit the form
        await wrapper.find('form').trigger('submit.prevent');

        await wrapper.vm.$nextTick();

        // Check that the success message is visible
        expect(wrapper.text()).toContain('Transfer successful!');

        // Check that the form's reset method was called
        expect(mockFormState.reset).toHaveBeenCalled();
    });
});