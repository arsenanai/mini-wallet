/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution');

module.exports = {
    root: true,
    extends: [
        'plugin:vue/vue3-essential',
        'eslint:recommended',
        '@vue/eslint-config-typescript',
        '@vue/eslint-config-prettier/skip-formatting',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        // This is the crucial part: It tells ESLint to use your tsconfig.json
        // for type-aware linting.
        project: ['./tsconfig.json', './tsconfig.node.json'],
        tsconfigRootDir: __dirname,
    },
    rules: {
        // You can add any project-specific rules here.
        // For example, to allow `console.log` in development:
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    },
    ignorePatterns: [
        'node_modules/',
        'vendor/',
        'public/',
        'bootstrap/cache/',
    ],
    overrides: [
        {
            // Disable the multi-word component name rule for page components
            // as they are not reused as tags and pose no risk of HTML tag collision.
            files: ['resources/js/Pages/**/*.vue'],
            rules: {
                'vue/multi-word-component-names': 'off',
            },
        },
        {
            // Also disable for base components that are unlikely to cause conflicts.
            // A better long-term solution is to rename them (e.g., AppModal, BaseCheckbox).
            files: ['resources/js/Components/**/*.vue'],
            rules: {
                'vue/multi-word-component-names': 'off',
            },
        },
    ],
};