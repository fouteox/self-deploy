import * as esbuild from 'esbuild';

const isDev = process.argv.includes('--dev');

async function compile() {
    try {
        await esbuild.build({
            entryPoints: ['resources/js/custom-highlight.js'],
            outfile: 'resources/js/dist/highlight.js', // Chemin de sortie du fichier compilé
            bundle: true,
            minify: !isDev,
            sourcemap: isDev ? 'inline' : false,
            platform: 'browser',
            target: ['es2020']
        });
        console.log('Build completed successfully');
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
}

compile();
