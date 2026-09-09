import { beforeEach, describe, expect, it } from 'vitest';
import { createStorage } from '../../resources/js/core/storage.js';
import { clearStorage } from './helpers.js';

describe('storage', () => {
    beforeEach(() => clearStorage());

    it('round-trips a record through cookie and localStorage', () => {
        const storage = createStorage({ name: 'cc_test', days: 30 });
        const record = { id: 'abc', v: '1', ts: '2026-01-01T00:00:00.000Z', c: { necessary: true, statistics: true } };

        storage.write(record);

        expect(document.cookie).toContain('cc_test=');
        expect(localStorage.getItem('cc_test')).not.toBeNull();
        expect(storage.read()).toEqual(record);
    });

    it('prefers the newest of the two copies', () => {
        const storage = createStorage({ name: 'cc_test', days: 30 });

        storage.write({ id: 'old', v: '1', ts: '2026-01-01T00:00:00.000Z', c: {} });
        localStorage.clear();
        localStorage.setItem('cc_test', btoa(JSON.stringify({ id: 'new', v: '1', ts: '2026-02-01T00:00:00.000Z', c: {} })));

        expect(storage.read().id).toBe('new');
    });

    it('returns null for garbage and after clear', () => {
        const storage = createStorage({ name: 'cc_test', days: 30 });

        document.cookie = 'cc_test=%%%not-base64';
        expect(storage.read()).toBeNull();

        storage.write({ id: 'x', v: '1', ts: 'now', c: {} });
        storage.clear();
        expect(storage.read()).toBeNull();
    });
});
