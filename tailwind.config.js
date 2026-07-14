import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Klasy budowane dynamicznie w Blade (partials/site_footer.blade.php używa
    // `hover:text-{{ $accent }}-400` itd.) nie są wykrywane przez skaner Tailwind,
    // więc trzeba je jawnie zachować, inaczej zostaną usunięte w buildzie.
    safelist: [
        'hover:text-rose-400',
        'hover:text-emerald-400',
        'hover:border-rose-400/40',
        'hover:border-emerald-400/40',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
