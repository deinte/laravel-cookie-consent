export function classify(value, rules) {
    if (!value || typeof value !== 'string') {
        return null;
    }

    for (let index = 0; index < rules.length; index++) {
        const rule = rules[index];

        if (rule && rule.pattern && value.indexOf(rule.pattern) !== -1) {
            return rule.category || null;
        }
    }

    return null;
}

export function isFirstParty(url) {
    if (!url || /^(data:|blob:|about:|javascript:|#)/i.test(url)) {
        return true;
    }

    if (!/^(https?:)?\/\//i.test(url)) {
        return true;
    }

    try {
        return new URL(url, location.href).host === location.host;
    } catch (error) {
        return true;
    }
}
