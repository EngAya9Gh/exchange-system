import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Cairo', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    500: '#8b5cf6', // A vibrant, modern purple/indigo (like image 2)
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                },
                secondary: {
                    500: '#f59e0b',
                    600: '#d97706',
                },
                success: '#10b981',
                danger: '#ef4444',
            },
            boxShadow: {
                'soft': '0 8px 30px rgba(0, 0, 0, 0.04)',
                'soft-xl': '0 20px 40px rgba(0, 0, 0, 0.06)',
            }
        },
    },

    plugins: [forms],
};
