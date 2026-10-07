<<<<<<< HEAD
// ESLint flat config native — Next.js 16 App Router (pas de FlatCompat).
const js = require('@eslint/js');
const tseslint = require('typescript-eslint');
const reactPlugin = require('eslint-plugin-react');
const reactHooks = require('eslint-plugin-react-hooks');
=======
// ESLint flat config — Next.js + TypeScript strict (CDC : aucun `any` toléré).
const js = require('@eslint/js');
const tseslint = require('typescript-eslint');
>>>>>>> main

module.exports = tseslint.config(
  { ignores: ['.next/**', 'node_modules/**', 'out/**'] },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ['src/**/*.{ts,tsx}'],
<<<<<<< HEAD
    plugins: { react: reactPlugin, 'react-hooks': reactHooks },
    languageOptions: {
      parserOptions: { ecmaFeatures: { jsx: true } },
    },
    settings: { react: { version: 'detect' } },
    rules: {
      // CDC : aucun `any` toléré.
      '@typescript-eslint/no-explicit-any': 'error',
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      'react/react-in-jsx-scope': 'off',
=======
    rules: {
      '@typescript-eslint/no-explicit-any': 'error',
>>>>>>> main
      '@typescript-eslint/no-unused-vars': [
        'error',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
      ],
    },
  }
);
<<<<<<< HEAD

=======
>>>>>>> main
