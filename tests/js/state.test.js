import { describe, expect, it } from 'vitest';
import { buildRecord, grantedCategories, isValidRecord } from '../../resources/js/core/state.js';
import { baseConfig } from './helpers.js';

describe('state', () => {
    it('invalidates records from another policy version or hash', () => {
        const config = baseConfig();
        const record = buildRecord(['statistics'], config, null);

        expect(isValidRecord(record, config)).toBe(true);
        expect(isValidRecord(record, baseConfig({ version: '2' }))).toBe(false);
        expect(isValidRecord(record, baseConfig({ policyHash: 'other' }))).toBe(false);
        expect(isValidRecord(null, config)).toBe(false);
        expect(isValidRecord({ v: '1' }, config)).toBe(false);
    });

    it('always grants necessary and keeps the consent id across decisions', () => {
        const config = baseConfig();
        const first = buildRecord([], config, null);
        const second = buildRecord(['marketing'], config, first);

        expect(grantedCategories(first)).toEqual(['necessary']);
        expect(grantedCategories(second)).toEqual(['necessary', 'marketing']);
        expect(second.id).toBe(first.id);
        expect(first.id).toMatch(/^[0-9a-f-]{36}$/);
    });
});
