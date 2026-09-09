export function logConsent(config, record) {
    if (!config.logEndpoint) {
        return false;
    }

    const payload = JSON.stringify({
        id: record.id,
        categories: Object.keys(record.c).filter((category) => record.c[category]),
        version: record.v,
        policyHash: record.h,
        url: location.href.slice(0, 2048),
    });

    if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
        try {
            if (navigator.sendBeacon(config.logEndpoint, new Blob([payload], { type: 'application/json' }))) {
                return true;
            }
        } catch (error) {
            // Fall through to fetch.
        }
    }

    if (typeof fetch === 'function') {
        fetch(config.logEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: payload,
            keepalive: true,
            credentials: 'same-origin',
        }).catch(() => {});

        return true;
    }

    return false;
}
