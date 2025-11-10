module.exports = {
    testEnvironment: 'jsdom',
    testMatch: [
        '**/tests/javascript/**/*.test.js'
    ],
    collectCoverageFrom: [
        'resources/assets/js/**/*.js',
        '!resources/assets/js/laroute.js'
    ],
    moduleFileExtensions: ['js', 'json'],
    transform: {
        '^.+\\.js$': 'babel-jest'
    },
    transformIgnorePatterns: [
        '/node_modules/'
    ],
    setupFilesAfterEnv: ['<rootDir>/tests/javascript/setup.js']
};
