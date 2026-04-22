import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Modules/**/Views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    safelist: [
        // Schutzbedarf-Dots (dynamisch per PHP-Array generiert)
        'bg-green-500', 'bg-yellow-400', 'bg-red-500', 'bg-gray-400',
        'text-green-700', 'text-yellow-700', 'text-red-700',
        // Dot-Größe (w-2 h-2) falls noch nicht durch statische Scans erkannt
        'w-2', 'h-2',
        // Revisions-Fälligkeit (dynamisch per PHP-Bedingung)
        'border-l-red-400',
        // Carry-over-Modal & Übertragungs-Button (HH)
        'bg-teal-600', 'hover:bg-teal-700',
        'text-teal-600', 'focus:ring-teal-500',
    ],

    plugins: [forms],
};
