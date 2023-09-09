import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    darkMode: 'class',
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
