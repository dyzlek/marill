import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            refresh: true,
            fonts: [
                // Fichiers rangés dans le dépôt (licence OFL) : le build ne
                // dépend plus d'un service extérieur et le navigateur ne
                // contacte aucun tiers pour afficher la police.
                local('Instrument Sans', {
                    variants: [400, 500, 600].map((weight) => ({
                        src: `resources/fonts/instrument-sans/instrument-sans-latin-${weight}-normal.woff2`,
                        weight,
                    })),
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
