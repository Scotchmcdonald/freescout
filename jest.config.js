module.exports = {
  testEnvironment: 'jsdom',
  testMatch: [
    '**/tests/javascript/**/*.test.js'
  ],
  collectCoverageFrom: [
    'resources/assets/js/**/*.js',
    'public/js/**/*.js',
    '!**/node_modules/**',
    '!**/vendor/**'
  ],
  moduleFileExtensions: ['js', 'json'],
  transform: {},
  testPathIgnorePatterns: ['/node_modules/', '/vendor/']
};
