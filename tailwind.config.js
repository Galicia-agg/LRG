import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Azul Cobalto / Marino — color de marca: confianza, seguridad, experiencia técnica.
                primary: {
                    50: '#eff4fc',
                    100: '#dce7f8',
                    200: '#b9d0f2',
                    300: '#8db3e8',
                    400: '#5c8fd8',
                    500: '#3569c2',
                    600: '#2851a3',
                    700: '#1f3f81',
                    800: '#1a3468',
                    900: '#142a54',
                    950: '#0c1930',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(20 42 84 / 0.04), 0 1px 3px 0 rgb(20 42 84 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
