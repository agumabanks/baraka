document.addEventListener('DOMContentLoaded', () => {
    // Dark Mode Toggle
    const themeToggleBtn = document.getElementById('theme-toggle');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');

    // Check for saved user preference, if any, on load of the website
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        lightIcon?.classList.remove('hidden');
        darkIcon?.classList.add('hidden');
    } else {
        document.documentElement.classList.remove('dark');
        lightIcon?.classList.add('hidden');
        darkIcon?.classList.remove('hidden');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            // toggle icons inside button
            darkIcon.classList.toggle('hidden');
            lightIcon.classList.toggle('hidden');

            // if set via local storage previously
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                // if NOT set via local storage previously
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
        });
    }

    // Spotlight Search
    const searchInput = document.querySelector('[data-preference-search]');
    const cards = document.querySelectorAll('[data-preference-card]');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            
            cards.forEach(card => {
                const tags = (card.dataset.tags || '').toLowerCase();
                const title = (card.querySelector('h3')?.textContent || '').toLowerCase();
                const desc = (card.querySelector('p')?.textContent || '').toLowerCase();
                
                if (!query || tags.includes(query) || title.includes(query) || desc.includes(query)) {
                    card.classList.remove('hidden');
                    // Add animation for appearance
                    card.classList.add('animate-fade-in');
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('animate-fade-in');
                }
            });
        });
    }
});
