import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { globSync } from 'glob';

export default defineConfig({
    // La minification CSS d'esbuild signale `calc(2px+var(--tw-ring-offset-width))`
    // (généré par les classes ring-* de Tailwind/Filament, pas par notre code)
    // comme un calc() "invalide" faute d'espaces autour du `+`. La syntaxe est
    // pourtant valide et fonctionne dans tous les navigateurs — on coupe juste
    // ce bruit pour garder une sortie d'installation propre et professionnelle.
    esbuild: {
        logOverride: {
            'invalid-calc': 'silent',
        },
    },
    plugins: [
        laravel({
            input: [
                ...globSync('resources/css/**/*.css'),
                ...globSync('resources/js/**/*.js'),

                ...globSync('plugins/*/resources/css/**/*.css'),
                ...globSync('plugins/*/resources/js/**/*.js'),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
