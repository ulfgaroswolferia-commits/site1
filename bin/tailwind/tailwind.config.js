// Konfiguracja budowania assets/css/b2b.css (Tailwind CSS v3, samodzielna binarka — bez npm).
// Instrukcja budowania: bin/tailwind/README.md
const path = require('path');
const root = path.join(__dirname, '..', '..');

module.exports = {
    content: [
        path.join(root, 'views/b2b/**/*.php'),
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
