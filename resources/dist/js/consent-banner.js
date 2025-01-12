
// Function to register the consent banner component
function registerConsentBanner(Alpine) {
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

            // Always dispatch the event first
            this.dispatchConsentEvent(accepted);

            // Get CSRF token if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.warn('[Consent Banner] CSRF token not found, skipping server sync');
                return;
            }

            // Send consent to server
            fetch('/enhanced-analytics/consent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    consent: accepted,
                    settings: this.settings
                })
            }).catch(error => {
                console.error('[Consent Banner] Error saving consent:', error);
            });
        },

        dispatchConsentEvent(accepted) {
            window.dispatchEvent(new CustomEvent('analytics-consent-changed', {
                detail: {
                    consent: accepted,
                    settings: this.settings
                }
            }));
        }
    }));
}

// Load Alpine.js and initialize the component
document.addEventListener('alpine:init', () => {
    registerConsentBanner(window.Alpine);
});

// Load Alpine.js if not already loaded
if (window.Alpine) {
    registerConsentBanner(window.Alpine);
} else {
    import('https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js')
        .then(() => {
            // Alpine.js will automatically start and trigger alpine:init
        })
        .catch(error => {
            console.error('[Consent Banner] Failed to load Alpine.js:', error);
        });
}
