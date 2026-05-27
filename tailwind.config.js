import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                racing: {
                    900: '#0a0a0f',
                    800: '#111118',
                    700: '#1a1a24',
                    600: '#252532',
                },
                accent: {
                    red: '#ef4444',
                    orange: '#f97316',
                    blue: '#3b82f6',
                    neon: '#22d3ee',
                    green: '#10b981',
                    silver: '#94a3b8',
                },
            },
        },
    },

    plugins: [forms],
};
