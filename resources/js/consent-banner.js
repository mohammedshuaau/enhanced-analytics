// Initialize Alpine if it's not already initialized
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}

// Register the consent banner component
Alpine.data('consentBanner', () => ({
    show: true,
    showSettings: false,
    settings: {
        geolocation: true
    },
    init() {
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
    },
    toggleGeolocation() {
        this.settings.geolocation = !this.settings.geolocation;
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
    },
    saveConsent(accepted) {
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
