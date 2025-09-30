import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui'; // ★ 追加

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',   // ★ 追加（Blade以外のJS内クラスも拾う）
        './resources/**/*.vue',  // ★ 追加（使ってたら拾えるように）
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
        daisyui, // ★ 追加
    ],

    // ★ 追加：daisyUI テーマ定義（画像の2色）
    daisyui: {
        themes: [
            {
                namieflower: {
                    primary:   '#679ace',   // 水色（完了）
                    'primary-content': '#ffffff',
                    secondary: '#d493b5',   // ピンク（現在）
                    'secondary-content': '#ffffff',
                    accent:    '#d493b5',
                    neutral:   '#2b3440',
                    'base-100':'#ffffff',
                    info:      '#93c5fd',
                    success:   '#22c55e',
                    warning:   '#f59e0b',
                    error:     '#ef4444',
                },
            },
            'light', // フォールバック
        ],
    },
};
