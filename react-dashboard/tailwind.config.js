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
          black: '#000000',
          gray: {
            900: '#1a1a1a',
            800: '#2d2d2d',
            700: '#404040',
            600: '#666666',
            500: '#808080',
            400: '#999999',
            300: '#b3b3b3',
            200: '#cccccc',
            100: '#e6e6e6',
            50: '#f5f5f5',
          },
          white: '#ffffff',
        },
      },
      fontFamily: {
        primary: ['-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        subtle: '0 1px 3px rgba(0, 0, 0, 0.08)',
        normal: '0 2px 8px rgba(0, 0, 0, 0.12)',
        elevated: '0 4px 16px rgba(0, 0, 0, 0.16)',
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
      },
    },
  },
  plugins: [],
}