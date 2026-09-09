function ensureGtag() {
    window.dataLayer = window.dataLayer || [];

    if (typeof window.gtag !== 'function') {
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
    }
}

export function consentModePayload(granted) {
    const has = (category) => granted.indexOf(category) !== -1;
    const flag = (value) => (value ? 'granted' : 'denied');

    return {
        ad_storage: flag(has('marketing')),
        ad_user_data: flag(has('marketing')),
        ad_personalization: flag(has('marketing')),
        analytics_storage: flag(has('statistics')),
        functionality_storage: flag(has('preferences')),
        personalization_storage: flag(has('preferences')),
        security_storage: 'granted',
    };
}

export function dataLayerEvent(granted) {
    const has = (category) => granted.indexOf(category) !== -1;

    return {
        event: 'cookie_consent_update',
        cc_necessary: true,
        cc_preferences: has('preferences'),
        cc_statistics: has('statistics'),
        cc_marketing: has('marketing'),
    };
}

export function initConsentMode(config, granted) {
    if (!config.consentMode) {
        return;
    }

    ensureGtag();

    const defaults = consentModePayload(['necessary']);
    defaults.wait_for_update = 500;

    window.gtag('consent', 'default', defaults);

    if (granted && granted.length > 1) {
        window.gtag('consent', 'update', consentModePayload(granted));
        window.dataLayer.push(dataLayerEvent(granted));
    }
}

export function updateConsentMode(config, granted) {
    if (!config.consentMode) {
        return;
    }

    ensureGtag();
    window.gtag('consent', 'update', consentModePayload(granted));
    window.dataLayer.push(dataLayerEvent(granted));
}
