import { SCRIPT_SELECTOR, EMBED_SELECTOR } from './core/constants.js';
import { emit } from './core/events.js';

const SKIPPED_ATTRIBUTES = ['type', 'data-src', 'data-cookieconsent', 'data-cc-blocked', 'data-cc-activated'];

function cloneScript(original) {
    const script = document.createElement('script');

    Array.prototype.forEach.call(original.attributes, (attribute) => {
        if (SKIPPED_ATTRIBUTES.indexOf(attribute.name) !== -1) {
            return;
        }

        script.setAttribute(attribute.name, attribute.value);
    });

    const dataType = original.getAttribute('data-type');

    if (dataType) {
        script.type = dataType;
    }

    const src = original.getAttribute('data-src') || original.getAttribute('data-cc-src');

    if (src) {
        script.async = original.hasAttribute('async');
        script.setAttribute('src', src);
    } else {
        script.textContent = original.textContent;
    }

    return script;
}

export function createActivator() {
    const announced = {};

    function activateScripts(category) {
        document.querySelectorAll(SCRIPT_SELECTOR + '[data-cookieconsent="' + category + '"]:not([data-cc-activated])').forEach((original) => {
            original.setAttribute('data-cc-activated', '');
            original.parentNode.insertBefore(cloneScript(original), original.nextSibling);
        });
    }

    function activateEmbeds(category) {
        document.querySelectorAll(EMBED_SELECTOR + '[data-cookieconsent="' + category + '"]:not([data-cc-activated])').forEach((element) => {
            if (element.tagName === 'SCRIPT') {
                return;
            }

            element.setAttribute('data-cc-activated', '');
            element.setAttribute('src', element.getAttribute('data-cc-src'));
            element.classList.remove('cc-hidden');

            const placeholder = element.previousElementSibling;

            if (placeholder && placeholder.hasAttribute('data-cc-placeholder')) {
                placeholder.parentNode.removeChild(placeholder);
            }
        });
    }

    return {
        activate(categories) {
            categories.forEach((category) => {
                activateScripts(category);
                activateEmbeds(category);

                if (!announced[category]) {
                    announced[category] = true;
                    emit('cookieconsent:accept:' + category, { category });
                }
            });
        },
        hasPending(category) {
            return document.querySelector(SCRIPT_SELECTOR + '[data-cookieconsent="' + category + '"]:not([data-cc-activated])') !== null
                || document.querySelector(EMBED_SELECTOR + '[data-cookieconsent="' + category + '"]:not([data-cc-activated])') !== null;
        },
    };
}
