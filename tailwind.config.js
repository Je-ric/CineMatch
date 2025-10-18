import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                'primary-bg': '#0f172a',
                'secondary-bg': '#1e293b',
                'card-bg': '#334155',
                'accent': '#10b981',
                'accent-hover': '#059669',
                'text-primary': '#f8fafc',
                'text-secondary': '#cbd5e1',
                'text-muted': '#64748b',
                'border-color': '#475569',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
