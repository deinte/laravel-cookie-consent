import { beforeEach, describe, expect, it } from 'vitest';
import { consentModePayload, initConsentMode, updateConsentMode } from '../../resources/js/consent-mode.js';
import { baseConfig } from './helpers.js';

describe('consent mode', () => {
    beforeEach(() => {
        delete window.dataLayer;
        delete window.gtag;
    });

    it('pushes denied defaults before anything else', () => {
        initConsentMode(baseConfig(), null);

        expect(window.dataLayer.length).toBe(1);
        expect(window.dataLayer[0][0]).toBe('consent');
        expect(window.dataLayer[0][1]).toBe('default');
        expect(window.dataLayer[0][2].analytics_storage).toBe('denied');
        expect(window.dataLayer[0][2].ad_storage).toBe('denied');
        expect(window.dataLayer[0][2].security_storage).toBe('granted');
        expect(window.dataLayer[0][2].wait_for_update).toBe(500);
    });

    it('immediately updates when consent was stored earlier', () => {
        initConsentMode(baseConfig(), ['necessary', 'statistics']);

        expect(window.dataLayer.length).toBe(3);
        expect(window.dataLayer[1][1]).toBe('update');
        expect(window.dataLayer[1][2].analytics_storage).toBe('granted');
        expect(window.dataLayer[1][2].ad_storage).toBe('denied');
        expect(window.dataLayer[2]).toMatchObject({ event: 'cookie_consent_update', cc_statistics: true, cc_marketing: false });
    });

    it('explicitly updates for a stored reject-all decision', () => {
        initConsentMode(baseConfig(), ['necessary']);

        expect(window.dataLayer.length).toBe(3);
        expect(window.dataLayer[1][1]).toBe('update');
        expect(window.dataLayer[1][2]).toEqual({
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'granted',
        });
        expect(window.dataLayer[2]).toMatchObject({
            event: 'cookie_consent_update',
            cc_necessary: true,
            cc_preferences: false,
            cc_statistics: false,
            cc_marketing: false,
        });
    });

    it('maps categories to the v2 signals', () => {
        expect(consentModePayload(['necessary', 'marketing', 'preferences'])).toEqual({
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'granted',
            analytics_storage: 'denied',
            functionality_storage: 'granted',
            personalization_storage: 'granted',
            security_storage: 'granted',
        });
    });

    it('does nothing when consent mode is off', () => {
        initConsentMode(baseConfig({ consentMode: false }), null);
        updateConsentMode(baseConfig({ consentMode: false }), ['necessary']);

        expect(window.dataLayer).toBeUndefined();
    });
});
