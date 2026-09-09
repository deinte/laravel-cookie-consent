import { build } from 'esbuild';

await build({
    entryPoints: ['resources/js/cookie-consent.js'],
    bundle: true,
    minify: true,
    format: 'iife',
    target: ['es2018'],
    outfile: 'resources/dist/cookie-consent.min.js',
    legalComments: 'none',
    logLevel: 'info',
});

await build({
    entryPoints: ['resources/css/cookie-consent.css'],
    minify: true,
    outfile: 'resources/dist/cookie-consent.min.css',
    logLevel: 'info',
});
