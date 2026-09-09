import { CATEGORIES } from './constants.js';

function uuid() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0;
        const value = char === 'x' ? random : (random & 0x3) | 0x8;

        return value.toString(16);
    });
}

export function isValidRecord(record, config) {
    if (!record || typeof record !== 'object' || !record.c) {
        return false;
    }

    if (String(record.v) !== String(config.version)) {
        return false;
    }

    if (config.policyHash && record.h !== config.policyHash) {
        return false;
    }

    return true;
}

export function grantedCategories(record) {
    if (!record || !record.c) {
        return ['necessary'];
    }

    return CATEGORIES.filter((category) => category === 'necessary' || record.c[category] === true);
}

export function buildRecord(categories, config, previous) {
    const consent = {};

    CATEGORIES.forEach((category) => {
        consent[category] = category === 'necessary' || categories.indexOf(category) !== -1;
    });

    return {
        id: previous && previous.id ? previous.id : uuid(),
        v: String(config.version),
        h: config.policyHash || null,
        ts: new Date().toISOString(),
        c: consent,
    };
}
