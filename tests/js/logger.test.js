import { afterEach, describe, expect, it, vi } from 'vitest';
import { logConsent } from '../../resources/js/logger.js';
import { baseConfig } from './helpers.js';

const record = { id: 'id-1', v: '1', h: 'hash1', ts: 'now', c: { necessary: true, statistics: true, marketing: false } };

describe('logger', () => {
    afterEach(() => vi.restoreAllMocks());

    it('prefers sendBeacon with a JSON blob', async () => {
        const beacon = vi.fn(() => true);
        Object.defineProperty(navigator, 'sendBeacon', { value: beacon, configurable: true });

        expect(logConsent(baseConfig(), record)).toBe(true);
        expect(beacon).toHaveBeenCalledWith('/cookie-consent/log', expect.any(Blob));
        const text = await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsText(beacon.mock.calls[0][1]);
        });
        expect(JSON.parse(text)).toMatchObject({ id: 'id-1', categories: ['necessary', 'statistics'], version: '1', policyHash: 'hash1' });
    });

    it('falls back to fetch keepalive and skips when logging is off', () => {
        Object.defineProperty(navigator, 'sendBeacon', { value: undefined, configurable: true });
        const fetchMock = vi.fn(() => Promise.resolve());
        vi.stubGlobal('fetch', fetchMock);

        expect(logConsent(baseConfig(), record)).toBe(true);
        expect(fetchMock.mock.calls[0][1]).toMatchObject({ method: 'POST', keepalive: true });

        expect(logConsent(baseConfig({ logEndpoint: null }), record)).toBe(false);
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });
});
