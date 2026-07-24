/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./application/Views/**/*.php",
    "./public/assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          bg: '#F4F7F6',
          mint: '#E6F0ED',
          teal: {
            50: '#F0FDFA',
            100: '#CCFBF1',
            500: '#14B8A6',
            600: '#0D9488',
            700: '#0F766E',
          },
          orange: {
            500: '#F97316',
            600: '#EA580C',
          },
          dark: '#1F2937',
          gray: '#6B7280'
        }
      },
      borderRadius: {
        '2xl': '1.0rem',
        '3xl': '1.5rem',
        '4xl': '2.0rem'
      },
      boxShadow: {
        'soft': '0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.02)',
        'soft-hover': '0 20px 30px -10px rgba(0, 0, 0, 0.07), 0 10px 15px -5px rgba(0, 0, 0, 0.04)'
      }
    }
  },
  plugins: []
}
