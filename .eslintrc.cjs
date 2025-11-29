/* eslint-env node */

module.exports = {
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        '@vue/eslint-config-typescript/recommended',
        '@vue/eslint-config-prettier',
    ],
    root: true,
    parser: 'vue-eslint-parser',
    parserOptions: {
        ecmaVersion: 'latest',
    },
    rules: {
        // Default Laravel components are single-word
        'vue/multi-word-component-names': 'off',
    },
};