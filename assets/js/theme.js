// Theme management
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.applyTheme(this.theme);
        this.setupListeners();
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update any theme toggles on the page
        const themeToggles = document.querySelectorAll('[name="theme"]');
        themeToggles.forEach(toggle => {
            if (toggle.value === theme) {
                toggle.checked = true;
            }
        });
    }

    setupListeners() {
        // Listen for theme changes
        document.addEventListener('themeChange', (e) => {
            this.applyTheme(e.detail.theme);
        });

        // Setup theme toggles
        const themeToggles = document.querySelectorAll('[name="theme"]');
        themeToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                this.applyTheme(e.target.value);
            });
        });
    }

    static getInstance() {
        if (!this.instance) {
            this.instance = new ThemeManager();
        }
        return this.instance;
    }
} 