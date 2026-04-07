import { defineConfig } from 'pest';

export default defineConfig({
    test: {
        parallel: {
            enabled: true,
            processes: 4,
        },
        coverage: {
            enabled: false,
            provider: 'phpunit',
            reports: ['text', 'html'],
            exclude: [
                'tests/**',
                'vendor/**',
                'src/Database/**',
                'src/Console/**',
            ],
        },
    },
    plugins: [
        'pest-plugin-arch',
        'pest-plugin-mutate',
    ],
});
