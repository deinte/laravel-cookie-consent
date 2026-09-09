import { EMBED_SELECTOR } from '../core/constants.js';

export function renderPlaceholders(template, onAccept) {
    const source = template ? template.content.querySelector('[data-cc="placeholder"]') : null;

    if (!source) {
        return;
    }

    document.querySelectorAll('iframe' + EMBED_SELECTOR + ':not([data-cc-activated])').forEach((iframe) => {
        const previous = iframe.previousElementSibling;

        if (previous && previous.hasAttribute('data-cc-placeholder')) {
            return;
        }

        const placeholder = source.cloneNode(true);
        placeholder.hidden = false;
        placeholder.setAttribute('data-cc-placeholder', '');
        placeholder.removeAttribute('data-cc');

        const category = iframe.getAttribute('data-cookieconsent');
        const button = placeholder.querySelector('[data-cc="placeholder-accept"]');

        if (button) {
            button.addEventListener('click', () => onAccept(category));
        }

        iframe.classList.add('cc-hidden');
        iframe.parentNode.insertBefore(placeholder, iframe);
    });
}
