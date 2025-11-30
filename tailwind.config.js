import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/views/branch/**/*.blade.php',
        './resources/views/settings/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Inter var"', '"SF Pro Text"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                obsidian: {
                    900: '#0b0c0f',
                    800: '#11131a',
                    700: '#181b24',
                },
            },
            boxShadow: {
                inset: 'inset 0 1px 0 rgba(255,255,255,0.04)',
            },
        },
    },
    plugins: [],
};
