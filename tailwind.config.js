/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.js",
    "./assets/**/*.css",
  ],
  safelist: [
    'bg-green-600', 'text-white', 'border-green-600',
    'bg-green-300', 'text-green-900', 'border-green-300',
    'bg-yellow-300', 'text-yellow-900', 'border-yellow-300',
    'bg-red-200', 'text-red-900', 'border-red-200',
    'bg-red-600', 'border-red-600',
    // ADD THESE:
    'bg-gray-900',
    'text-gray-400',
    'text-gray-500',
    'border-gray-800',
    'hover:text-white',
  ],
  theme: {
    extend: { 
      colors: {
        primary: {
          DEFAULT: '#16a34a',
          foreground: '#ffffff',
        },
        secondary: '#dcfce7',
        card: '#ffffff',
        border: '#bbf7d0',
        muted: {
          foreground: '#4b5563',
        },
        destructive: '#dc2626',
      },
    },
  },
  plugins: [],
}