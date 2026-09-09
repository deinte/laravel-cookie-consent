import { beforeEach, describe, expect, it, vi } from 'vitest';
import { bootstrap } from '../../resources/js/cookie-consent.js';
import { baseConfig, clearStorage, installTemplate } from './helpers.js';

function flush() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

describe('bootstrap', () => {
    beforeEach(() => {
        clearStorage();
        delete window.dataLayer;
        delete window.gtag;
        Object.defineProperty(navigator, 'sendBeacon', { value: vi.fn(() => true), configurable: true });
        installTemplate();
    });

    it('shows the banner, applies the theme and activates scripts on accept', async () => {
        document.body.insertAdjacentHTML('beforeend', '<script type="text/plain" data-cookieconsent="statistics">window.__stats = true;</script>');
        const changed = vi.fn();
        document.addEventListener('cookieconsent:changed', changed);

        const api = bootstrap(baseConfig());
        await flush();

        const banner = document.querySelector('[data-cc="banner"]');
        expect(banner.hidden).toBe(false);
        expect(document.getElementById('cc-root').style.getPropertyValue('--cc-primary')).toBe('#123456');
        expect(api.hasDecided()).toBe(false);

        banner.querySelector('[data-cc="accept"]').click();

        expect(api.hasConsent('statistics')).toBe(true);
        expect(api.hasConsent('marketing')).toBe(true);
        expect(banner.hidden).toBe(true);
        expect(document.querySelector('script[data-cc-activated]')).not.toBeNull();
        expect(navigator.sendBeacon).toHaveBeenCalled();
        expect(changed.mock.calls[0][0].detail.after).toContain('marketing');
        expect(window.dataLayer.some((entry) => entry[1] === 'update')).toBe(true);
        expect(document.cookie).toContain('cc_test=');
    });

    it('saves a partial choice from the preferences modal and reopens through data attributes', async () => {
        document.body.insertAdjacentHTML('beforeend', '<a href="#" id="reopen" data-cookieconsent="show">settings</a>');
        const api = bootstrap(baseConfig());
        await flush();

        document.querySelector('[data-cc="banner"] [data-cc="manage"]').click();
        const modal = document.querySelector('[data-cc="modal"]');
        expect(modal.hidden).toBe(false);

        modal.querySelector('[data-cc-category="preferences"]').checked = true;
        modal.querySelector('[data-cc="save"]').click();

        expect(api.getGranted()).toEqual(['necessary', 'preferences']);
        expect(modal.hidden).toBe(true);

        document.getElementById('reopen').click();
        expect(modal.hidden).toBe(false);
        expect(modal.querySelector('[data-cc-category="preferences"]').checked).toBe(true);
        expect(modal.querySelector('[data-cc-category="statistics"]').checked).toBe(false);
    });

    it('re-prompts when the policy version changes but keeps the consent id', async () => {
        const first = bootstrap(baseConfig());
        await flush();
        first.acceptAll();
        const id = first.getConsent().id;

        installTemplate();
        const second = bootstrap(baseConfig({ version: '2' }));
        await flush();

        expect(second.hasDecided()).toBe(false);
        expect(document.querySelector('[data-cc="banner"]').hidden).toBe(false);

        second.rejectAll();
        expect(second.getConsent().id).toBe(id);
        expect(second.getGranted()).toEqual(['necessary']);
    });

    it('renders placeholders for blocked iframes and unblocks from them', async () => {
        document.body.insertAdjacentHTML('beforeend', '<iframe data-cookieconsent="marketing" data-cc-src="https://www.youtube.com/embed/x"></iframe>');
        const api = bootstrap(baseConfig());
        await flush();

        const placeholder = document.querySelector('[data-cc-placeholder]');
        expect(placeholder).not.toBeNull();
        expect(document.querySelector('iframe').classList.contains('cc-hidden')).toBe(true);

        placeholder.querySelector('[data-cc="placeholder-accept"]').click();

        expect(api.hasConsent('marketing')).toBe(true);
        expect(document.querySelector('iframe').getAttribute('src')).toBe('https://www.youtube.com/embed/x');
        expect(document.querySelector('[data-cc-placeholder]')).toBeNull();
    });
});
