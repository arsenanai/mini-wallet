import { mount } from '@vue/test-utils';
import Balance from '@/Components/Balance.vue';
import { describe, it, expect } from 'vitest';

describe('Balance.vue', () => {
    it('correctly formats a numeric balance into a currency string', () => {
        const balance = 123.45;
        const wrapper = mount(Balance, {
            props: { balance },
        });

        expect(wrapper.text()).toContain('$123.45');
    });
});
