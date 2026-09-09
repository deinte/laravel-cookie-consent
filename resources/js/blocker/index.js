import { classify, isFirstParty } from './rules.js';
import { TRANSPARENT_PIXEL } from '../core/constants.js';

const BLOCKABLE = { script: HTMLScriptElement, iframe: HTMLIFrameElement, img: HTMLImageElement };

export function createBlocker(config, hasConsent) {
    const rules = config.rules || [];
    let observer = null;
    let started = false;

    function categoryFor(url, tag) {
        const category = classify(url, rules);

        if (category) {
            return category;
        }

        if (config.blockUnknown && tag !== 'img' && !isFirstParty(url)) {
            return 'marketing';
        }

        return null;
    }

    function shouldBlock(url, tag) {
        const category = categoryFor(url, tag);

        if (!category || category === 'necessary') {
            return null;
        }

        return hasConsent(category) ? null : category;
    }

    function markBlocked(element, category, url, tag) {
        element.setAttribute('data-cookieconsent', category);
        element.setAttribute('data-cc-src', url);
        element.setAttribute('data-cc-blocked', '');

        if (tag === 'script') {
            element.type = 'text/plain';
        }
    }

    function guardElement(element, tag) {
        const proto = BLOCKABLE[tag].prototype;
        const descriptor = Object.getOwnPropertyDescriptor(proto, 'src');

        if (!descriptor || !descriptor.set) {
            return;
        }

        Object.defineProperty(element, 'src', {
            configurable: true,
            enumerable: descriptor.enumerable,
            get() {
                return descriptor.get.call(element);
            },
            set(value) {
                const url = String(value);
                const category = shouldBlock(url, tag);

                if (!category) {
                    descriptor.set.call(element, url);
                    return;
                }

                markBlocked(element, category, url, tag);
            },
        });

        const originalSetAttribute = element.setAttribute;

        element.setAttribute = function (name, value) {
            if (String(name).toLowerCase() === 'src') {
                element.src = value;
                return;
            }

            return originalSetAttribute.call(element, name, value);
        };
    }

    function hookCreateElement() {
        const original = document.createElement;

        document.createElement = function (tagName, options) {
            const element = original.call(document, tagName, options);
            const tag = String(tagName).toLowerCase();

            if (BLOCKABLE[tag]) {
                guardElement(element, tag);
            }

            return element;
        };
    }

    function neutralizeInserted(element) {
        const tag = element.tagName ? element.tagName.toLowerCase() : '';

        if (!BLOCKABLE[tag] || element.hasAttribute('data-cookieconsent') || element.hasAttribute('data-cc-src')) {
            return;
        }

        const url = element.getAttribute('src');

        if (tag === 'script') {
            const type = (element.getAttribute('type') || '').toLowerCase();

            if (type && type !== 'text/javascript' && type !== 'application/javascript' && type !== 'module') {
                return;
            }

            const category = shouldBlock(url || element.textContent || '', tag);

            if (!category) {
                return;
            }

            element.setAttribute('data-cookieconsent', category);
            element.setAttribute('data-cc-blocked', '');
            element.type = 'text/plain';

            if (url) {
                element.setAttribute('data-src', url);
                element.removeAttribute('src');
            }

            return;
        }

        if (!url) {
            return;
        }

        const category = shouldBlock(url, tag);

        if (!category) {
            return;
        }

        element.setAttribute('data-cookieconsent', category);
        element.setAttribute('data-cc-src', url);
        element.setAttribute('data-cc-blocked', '');
        element.setAttribute('src', tag === 'img' ? TRANSPARENT_PIXEL : 'about:blank');
    }

    function observe() {
        observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) {
                        return;
                    }

                    neutralizeInserted(node);

                    if (node.querySelectorAll) {
                        node.querySelectorAll('script,iframe,img').forEach(neutralizeInserted);
                    }
                });
            });
        });

        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    return {
        start() {
            if (started) {
                return;
            }

            started = true;
            hookCreateElement();
            observe();
        },
        stop() {
            if (observer) {
                observer.disconnect();
            }
        },
        classify: (url) => categoryFor(url, 'script'),
    };
}
