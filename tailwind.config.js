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
  ],
  theme: {
    extend: { colors: {
        primary: {
          DEFAULT: '#16a34a',        // green-600
          foreground: '#ffffff',
        },
        secondary: '#dcfce7',        // green-100
        card: '#ffffff',
        border: '#bbf7d0',           // green-200
        muted: {
          foreground: '#4b5563',     // gray-600
        },
        destructive: '#dc2626',      // red-600
      },},
  },
  plugins: [],
}