/** @type {import('jest').Config} */
module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/src'],
  testMatch: ['**/__tests__/**/*.test.{ts,tsx}', '**/*.test.{ts,tsx}'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
    '\\.(css|less|scss|sass)$': '<rootDir>/src/__mocks__/styleMock.js',
    // maplibre-gl v6 n'expose qu'une condition "import" (ESM) dans son champ
    // `exports` : le résolveur CommonJS de Jest ne peut pas le charger.
    // On le substitue par un mock sans rendu WebGL ni requête de tuiles.
    '^maplibre-gl$': '<rootDir>/src/__mocks__/maplibre-gl.ts',
  },
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
  transform: {
    '^.+\\.(ts|tsx)$': [
      'ts-jest',
      { tsconfig: '<rootDir>/tsconfig.json', isolatedModules: true },
    ],
  },
};
