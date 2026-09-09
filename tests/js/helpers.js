export function baseConfig(overrides = {}) {
    return {
        version: '1',
        policyHash: 'hash1',
        locale: 'en',
        policyUrl: '/cookies',
        logEndpoint: '/cookie-consent/log',
        consentMode: true,
        blockUnknown: false,
        reloadOnRevoke: false,
        showReject: true,
        cookie: { name: 'cc_test', days: 30, domain: null },
        layout: 'bar',
        position: 'bottom',
        theme: { '--cc-primary': '#123456' },
        categories: [
            { key: 'necessary', required: true, label: 'Necessary', description: '' },
            { key: 'preferences', required: false, label: 'Preferences', description: '' },
            { key: 'statistics', required: false, label: 'Statistics', description: '' },
            { key: 'marketing', required: false, label: 'Marketing', description: '' },
        ],
        texts: { title: 'Cookies', body: 'Body', accept_all: 'Accept', reject_all: 'Reject', manage: 'Manage', save: 'Save', close: 'Close' },
        rules: [
            { pattern: 'googletagmanager.com/gtag/js', category: 'statistics' },
            { pattern: 'googletagmanager.com', category: 'necessary' },
            { pattern: 'connect.facebook.net', category: 'marketing' },
            { pattern: 'youtube.com/embed', category: 'marketing' },
            { pattern: 'facebook.com/tr', category: 'marketing' },
        ],
        ...overrides,
    };
}

export function installTemplate() {
    document.body.innerHTML = `
        <template id="cc-template">
            <div class="cc-banner" role="dialog" data-cc="banner" hidden>
                <button type="button" data-cc="manage">Manage</button>
                <button type="button" data-cc="reject">Reject</button>
                <button type="button" data-cc="accept">Accept</button>
            </div>
            <div class="cc-modal" role="dialog" data-cc="modal" hidden>
                <div data-cc="backdrop"></div>
                <button type="button" data-cc="close">Close</button>
                <input type="checkbox" data-cc-category="necessary" checked disabled>
                <input type="checkbox" data-cc-category="preferences">
                <input type="checkbox" data-cc-category="statistics">
                <input type="checkbox" data-cc-category="marketing">
                <button type="button" data-cc="save">Save</button>
                <button type="button" data-cc="accept">Accept</button>
            </div>
            <div class="cc-placeholder" data-cc="placeholder" hidden>
                <button type="button" data-cc="placeholder-accept">Allow</button>
            </div>
        </template>
    `;
}

export function clearStorage(name = 'cc_test') {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    localStorage.clear();
}
