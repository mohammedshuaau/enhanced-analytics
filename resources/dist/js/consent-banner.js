console.log('[Consent Banner] Script loading...');

// Function to register the consent banner component
function registerConsentBanner(Alpine) {
    console.log('[Consent Banner] Registering component');
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
            console.log('[Consent Banner] Initial settings:', this.settings);
        },
        toggleGeolocation() {
            console.log('[Consent Banner] Toggling geolocation');
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
            console.log('[Consent Banner] Settings panel:', this.showSettings ? 'shown' : 'hidden');
        },
        saveConsent(accepted) {
            console.log('[Consent Banner] Saving consent:', accepted, 'with settings:', this.settings);

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
            console.log('[Consent Banner] Dispatching consent changed event:', { accepted, settings: this.settings });
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
    console.log('[Consent Banner] Alpine:init event triggered');
    registerConsentBanner(window.Alpine);
});

// Load Alpine.js if not already loaded
if (window.Alpine) {
    console.log('[Consent Banner] Using existing Alpine.js instance');
    registerConsentBanner(window.Alpine);
} else {
    console.log('[Consent Banner] Loading Alpine.js from CDN');
    import('https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js')
        .then(() => {
            console.log('[Consent Banner] Alpine.js loaded successfully');
            // Alpine.js will automatically start and trigger alpine:init
        })
        .catch(error => {
            console.error('[Consent Banner] Failed to load Alpine.js:', error);
        });
} 