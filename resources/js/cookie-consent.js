import { CATEGORIES, OPTIONAL_CATEGORIES } from './core/constants.js';
import { createStorage } from './core/storage.js';
import { buildRecord, grantedCategories, isValidRecord } from './core/state.js';
import { emit } from './core/events.js';
import { initConsentMode, updateConsentMode } from './consent-mode.js';
import { createBlocker } from './blocker/index.js';
import { createActivator } from './activator.js';
import { logConsent } from './logger.js';
import { createUi } from './ui/banner.js';
import { renderPlaceholders } from './ui/placeholders.js';

export function bootstrap(config) {
    const storage = createStorage(config.cookie || {});
    const activator = createActivator();
    const readyCallbacks = [];

    let record = storage.read();
    let decided = isValidRecord(record, config);
    let granted = decided ? grantedCategories(record) : ['necessary'];
    let ready = false;

    if (!decided && record) {
        record = { id: record.id };
    }

    const hasConsent = (category) => granted.indexOf(category) !== -1;

    const blocker = createBlocker(config, hasConsent);
    blocker.start();

    initConsentMode(config, decided ? granted : null);

    const ui = createUi(config, {
        accept: (categories) => api.accept(categories),
        granted: () => granted.slice(),
        hasDecided: () => decided,
    });

    function applyDecision(categories, source) {
        const before = granted.slice();
        const next = buildRecord(categories, config, record);
        const nextGranted = grantedCategories(next);
        const revoked = before.filter((category) => nextGranted.indexOf(category) === -1);

        record = next;
        decided = true;
        granted = nextGranted;
        storage.write(record);

        updateConsentMode(config, granted);
        activator.activate(granted);
        logConsent(config, record);
        ui.hideAll();

        emit('cookieconsent:changed', { before, after: granted.slice(), source });

        if (revoked.length > 0 && config.reloadOnRevoke) {
            location.reload();
        }
    }

    const api = {
        version: config.version,
        show() {
            ui.openModal();
        },
        showBanner() {
            ui.showBanner();
        },
        hide() {
            ui.hideAll();
        },
        acceptAll() {
            applyDecision(OPTIONAL_CATEGORIES.slice(), 'api');
        },
        rejectAll() {
            applyDecision([], 'api');
        },
        accept(categories) {
            applyDecision((categories || []).filter((category) => CATEGORIES.indexOf(category) !== -1), 'ui');
        },
        hasConsent,
        hasDecided() {
            return decided;
        },
        getConsent() {
            return decided ? JSON.parse(JSON.stringify(record)) : null;
        },
        getGranted() {
            return granted.slice();
        },
        reset() {
            storage.clear();
            record = null;
            decided = false;
            granted = ['necessary'];
            ui.showBanner();
        },
        onReady(callback) {
            if (ready) {
                callback(api.getConsent());
                return;
            }

            readyCallbacks.push(callback);
        },
        categories: CATEGORIES.slice(),
    };

    function onDomReady() {
        activator.activate(granted);
        renderPlaceholders(document.getElementById('cc-template'), (category) => {
            api.accept(granted.concat(category ? [category] : []));
        });

        if (!decided) {
            ui.showBanner();
        }

        ready = true;
        emit('cookieconsent:ready', { consent: api.getConsent(), granted: granted.slice() });
        readyCallbacks.splice(0).forEach((callback) => callback(api.getConsent()));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDomReady);
    } else {
        onDomReady();
    }

    return api;
}

if (typeof window !== 'undefined' && window.CookieConsentConfig && !window.CookieConsent) {
    window.CookieConsent = bootstrap(window.CookieConsentConfig);
}
