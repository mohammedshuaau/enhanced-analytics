import Alpine from 'alpinejs';

// Debug: Script load
console.log('[Consent Banner] Script loading...');

// Initialize Alpine if it's not already initialized
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
    console.log('[Consent Banner] Alpine.js initialized');
}

// Register the consent banner component
Alpine.data('consentBanner', () => ({
    show: true,
    showSettings: false,
    settings: {
        geolocation: true
    },
    init() {
        console.log('[Consent Banner] Component initialized');
        // Check if consent is already stored
        const storedConsent = localStorage.getItem('analytics_consent');
        if (storedConsent) {
            this.show = false;
            return;
        }
        // Check if settings are stored
        const storedSettings = localStorage.getItem('analytics_settings');
        if (storedSettings) {
            this.settings = JSON.parse(storedSettings);
        }
        console.log('Initial settings:', this.settings);
    },
    toggleGeolocation() {
        console.log('Toggling geolocation');
        this.settings.geolocation = !this.settings.geolocation;
        console.log('New geolocation value:', this.settings.geolocation);
    },
    accept() {
        this.saveConsent(true);
        this.show = false;
    },
    decline() {
        this.saveConsent(false);
        this.show = false;
    },
    toggleSettings() {
        this.showSettings = !this.showSettings;
        console.log('Settings panel:', this.showSettings ? 'shown' : 'hidden');
    },
    saveConsent(accepted) {
        console.log('Saving consent:', accepted, 'with settings:', this.settings);
        // Store consent in localStorage
        localStorage.setItem('analytics_consent', accepted ? 'accepted' : 'declined');
        localStorage.setItem('analytics_settings', JSON.stringify(this.settings));

        // Send consent to server
        fetch('/enhanced-analytics/consent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                consent: accepted,
                settings: this.settings
            })
        }).catch(error => console.error('Error saving consent:', error));

        // Dispatch event for other scripts
        window.dispatchEvent(new CustomEvent('analytics-consent-changed', {
            detail: {
                consent: accepted,
                settings: this.settings
            }
        }));
    }
})); 