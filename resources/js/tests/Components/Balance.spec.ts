import Balance from '@/Components/Balance.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('Balance.vue', () => {
    it('correctly formats a numeric balance into a currency string', () => {
        const balance = 123.45;
        const wrapper = mount(Balance, {
            props: { balance },
            global: {
                mocks: {
                    $t: (key: string) => key, // Simple mock that returns the key
                },
            },
        });

        expect(wrapper.text()).toContain('$123.45');
    });
});
