import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createActivator } from '../../resources/js/activator.js';

describe('activator', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('turns managed scripts into executable clones once and fires the category event', () => {
        document.body.innerHTML = `
            <script type="text/plain" data-cookieconsent="statistics" data-src="https://x.test/a.js" async data-foo="bar"></script>
            <script type="text/plain" data-cookieconsent="statistics">window.__inline = 1;</script>
            <script type="text/plain" data-cookieconsent="marketing" data-src="https://x.test/m.js"></script>
        `;
        const handler = vi.fn();
        document.addEventListener('cookieconsent:accept:statistics', handler);

        const activator = createActivator();
        activator.activate(['necessary', 'statistics']);
        activator.activate(['necessary', 'statistics']);

        const clones = document.querySelectorAll('script:not([type="text/plain"])');
        expect(clones.length).toBe(2);
        expect(clones[0].getAttribute('src')).toBe('https://x.test/a.js');
        expect(clones[0].async).toBe(true);
        expect(clones[0].getAttribute('data-foo')).toBe('bar');
        expect(clones[0].hasAttribute('data-cookieconsent')).toBe(false);
        expect(clones[1].textContent).toBe('window.__inline = 1;');
        expect(document.querySelectorAll('[data-cc-activated]').length).toBe(2);
        expect(activator.hasPending('marketing')).toBe(true);
        expect(activator.hasPending('statistics')).toBe(false);
        expect(handler).toHaveBeenCalledTimes(1);
    });

    it('restores embeds and removes their placeholders', () => {
        document.body.innerHTML = `
            <div data-cc-placeholder></div>
            <iframe data-cookieconsent="marketing" data-cc-src="https://www.youtube.com/embed/1" class="cc-hidden"></iframe>
            <img data-cookieconsent="marketing" data-cc-src="https://www.facebook.com/tr?id=1">
        `;

        createActivator().activate(['marketing']);

        const iframe = document.querySelector('iframe');
        expect(iframe.getAttribute('src')).toBe('https://www.youtube.com/embed/1');
        expect(iframe.classList.contains('cc-hidden')).toBe(false);
        expect(document.querySelector('[data-cc-placeholder]')).toBeNull();
        expect(document.querySelector('img').getAttribute('src')).toBe('https://www.facebook.com/tr?id=1');
    });
});
