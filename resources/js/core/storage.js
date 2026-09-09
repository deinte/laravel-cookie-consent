function encode(value) {
    const json = JSON.stringify(value);

    return btoa(unescape(encodeURIComponent(json)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

function decode(raw) {
    if (!raw) {
        return null;
    }

    try {
        const base64 = raw.replace(/-/g, '+').replace(/_/g, '/');
        const padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4);

        return JSON.parse(decodeURIComponent(escape(atob(padded))));
    } catch (error) {
        return null;
    }
}

function readCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));

    return match ? decode(match[1]) : null;
}

function writeCookie(name, value, days, domain) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    let cookie = name + '=' + encode(value) + '; expires=' + expires + '; path=/; SameSite=Lax';

    if (domain) {
        cookie += '; domain=' + domain;
    }

    if (location.protocol === 'https:') {
        cookie += '; Secure';
    }

    document.cookie = cookie;
}

function deleteCookie(name, domain) {
    let cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';

    if (domain) {
        cookie += '; domain=' + domain;
    }

    document.cookie = cookie;
}

function readLocal(name) {
    try {
        return decode(localStorage.getItem(name));
    } catch (error) {
        return null;
    }
}

function writeLocal(name, value) {
    try {
        localStorage.setItem(name, encode(value));
    } catch (error) {
        // Storage may be unavailable (private mode, quota); the cookie still holds the decision.
    }
}

function removeLocal(name) {
    try {
        localStorage.removeItem(name);
    } catch (error) {
        // Ignore.
    }
}

export function createStorage(cookieConfig) {
    const name = cookieConfig.name || 'cc_consent';
    const days = cookieConfig.days || 180;
    const domain = cookieConfig.domain || null;

    return {
        read() {
            const fromCookie = readCookie(name);
            const fromLocal = readLocal(name);

            if (fromCookie && fromLocal) {
                return Date.parse(fromLocal.ts || 0) > Date.parse(fromCookie.ts || 0) ? fromLocal : fromCookie;
            }

            return fromCookie || fromLocal || null;
        },
        write(value) {
            writeCookie(name, value, days, domain);
            writeLocal(name, value);
        },
        clear() {
            deleteCookie(name, domain);
            removeLocal(name);
        },
    };
}
