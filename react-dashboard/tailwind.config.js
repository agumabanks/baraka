/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        mono: {
          black: '#111111',
          white: '#ffffff',
          gray: {
            25: '#fcfcfc',
            50: '#fafafa',
            100: '#f3f4f6',
            200: '#e5e7eb',
            300: '#d1d5db',
            400: '#9ca3af',
            500: '#6b7280',
            600: '#4b5563',
            700: '#404040',
            800: '#2d2d2d',
            900: '#1f2937',
          },
        },
      },
      borderRadius: {
        'xl': '18px',
        '2xl': '20px',
      },
      fontFamily: {
        primary: ['-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        subtle: '0 1px 3px rgba(0, 0, 0, 0.08)',
        normal: '0 2px 8px rgba(0, 0, 0, 0.12)',
        elevated: '0 4px 16px rgba(0, 0, 0, 0.16)',
        lg: '0 18px 36px rgba(15, 23, 42, 0.05)',
        xl: '0 32px 70px rgba(15, 23, 42, 0.08)',
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
    },
  },
  plugins: [],
}
