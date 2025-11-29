import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import en from '../locales/en.json';

type NestedTranslations = { [key: string]: string | NestedTranslations };

// A realistic i18n mock that performs translations
const t = (key: string | number, values?: Record<string, string>): string => {
    const keys = String(key).split('.');
    let text: string | NestedTranslations = en as NestedTranslations;
    for (const k of keys) {
        if (
            text &&
            typeof text === 'object' &&
            Object.prototype.hasOwnProperty.call(text, k)
        ) {
            text = text[k] as string | NestedTranslations;
        } else {
            return String(key); // Return key as a string if not found
        }
    }

    if (values && typeof text === 'string') {
        return text.replace(
            /{(\w+)}/g,
            (_, placeholder) => values[placeholder] || placeholder,
        );
    }
    return text as string;
};

// Make the mock available globally in all tests
config.global.mocks = {
    $t: t,
};

// Mock the global route() helper used in many components
vi.stubGlobal('route', (name?: string) => {
    if (name) return `http://localhost/${name}`;
    return { current: () => false };
});
