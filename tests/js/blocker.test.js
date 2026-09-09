import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { createBlocker } from '../../resources/js/blocker/index.js';
import { baseConfig } from './helpers.js';

const originalCreateElement = document.createElement;

function flush() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

describe('blocker', () => {
    let granted;
    let blocker;

    beforeEach(() => {
        granted = ['necessary'];
        document.body.innerHTML = '';
    });

    afterEach(() => {
        blocker.stop();
        document.createElement = originalCreateElement;
    });

    it('neutralises dynamically created scripts for categories without consent', () => {
        blocker = createBlocker(baseConfig(), (category) => granted.indexOf(category) !== -1);
        blocker.start();

        const script = document.createElement('script');
        script.src = 'https://connect.facebook.net/en_US/fbevents.js';

        expect(script.type).toBe('text/plain');
        expect(script.getAttribute('data-cookieconsent')).toBe('marketing');
        expect(script.getAttribute('data-cc-src')).toBe('https://connect.facebook.net/en_US/fbevents.js');
        expect(script.getAttribute('src')).toBeNull();

        const allowed = document.createElement('script');
        allowed.setAttribute('src', 'https://www.googletagmanager.com/gtm.js?id=GTM-1');
        expect(allowed.getAttribute('src')).toBe('https://www.googletagmanager.com/gtm.js?id=GTM-1');
        expect(allowed.type).not.toBe('text/plain');

        granted = ['necessary', 'marketing'];
        const later = document.createElement('script');
        later.src = 'https://connect.facebook.net/en_US/fbevents.js';
        expect(later.getAttribute('src')).toBe('https://connect.facebook.net/en_US/fbevents.js');
    });

    it('rewrites parser-inserted scripts, iframes and beacons via the observer', async () => {
        blocker = createBlocker(baseConfig(), (category) => granted.indexOf(category) !== -1);
        blocker.start();

        const wrapper = originalCreateElement.call(document, 'div');
        wrapper.innerHTML = `
            <script src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>
            <script type="application/ld+json">{}</script>
            <iframe src="https://www.youtube.com/embed/abc"></iframe>
            <img src="https://www.facebook.com/tr?id=1">
            <img src="/local.png">
        `;
        document.body.appendChild(wrapper);
        await flush();

        const [tracker, json] = wrapper.querySelectorAll('script');
        expect(tracker.type).toBe('text/plain');
        expect(tracker.getAttribute('data-src')).toBe('https://www.googletagmanager.com/gtag/js?id=G-1');
        expect(tracker.getAttribute('data-cookieconsent')).toBe('statistics');
        expect(json.getAttribute('type')).toBe('application/ld+json');

        const iframe = wrapper.querySelector('iframe');
        expect(iframe.getAttribute('src')).toBe('about:blank');
        expect(iframe.getAttribute('data-cc-src')).toBe('https://www.youtube.com/embed/abc');

        const [pixel, local] = wrapper.querySelectorAll('img');
        expect(pixel.getAttribute('src')).toMatch(/^data:image\/gif/);
        expect(local.getAttribute('src')).toBe('/local.png');
    });

    it('optionally blocks unknown third parties', () => {
        blocker = createBlocker(baseConfig({ blockUnknown: true }), () => false);
        blocker.start();

        const unknown = document.createElement('script');
        unknown.src = 'https://cdn.unknown-tracker.io/t.js';
        expect(unknown.getAttribute('data-cookieconsent')).toBe('marketing');

        const firstParty = document.createElement('script');
        firstParty.src = '/build/app.js';
        expect(firstParty.getAttribute('src')).toBe('/build/app.js');
    });
});
