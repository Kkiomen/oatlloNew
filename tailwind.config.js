import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Klasy budowane dynamicznie w Blade (akcent per-kurs `text-{{ $accent }}-400`
    // itd. oraz footer `hover:text-{{ $accent }}-400`) nie są wykrywane przez skaner
    // Tailwind, więc trzeba je jawnie zachować, inaczej zostaną usunięte w buildzie.
    //
    // $accent to nazwa palety Tailwind zwracana przez CourseCoverImageService::accentColor()
    // (config/course-covers.php -> accent_color). Dodając NOWY kolor akcentu kursu,
    // dopisz go tutaj do `accentColors`.
    safelist: (() => {
        const accentColors = ['emerald', 'rose', 'sky', 'blue', 'red', 'green', 'amber', 'cyan', 'orange', 'violet'];
        const variants = (c) => [
            `bg-${c}-400/10`, `bg-${c}-500`, `bg-${c}-500/15`,
            `border-${c}-400`, `border-${c}-400/20`, `border-${c}-400/30`, `border-${c}-500/20`,
            `from-${c}-500/15`, `to-${c}-500/15`,
            `group-hover:text-${c}-300`,
            `hover:bg-${c}-400`, `hover:bg-${c}-400/20`,
            `hover:text-${c}-300`, `hover:text-${c}-400`,
            `hover:border-${c}-400/40`,
            `shadow-${c}-500/30`,
            `text-${c}-300`, `text-${c}-400`,
            `ring-${c}-400`, `ring-${c}-500/30`,
        ];
        return accentColors.flatMap(variants);
    })(),

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
