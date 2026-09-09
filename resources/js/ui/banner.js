import { OPTIONAL_CATEGORIES } from '../core/constants.js';
import { createFocusTrap } from './focus-trap.js';

export function createUi(config, actions) {
    let root = null;
    let banner = null;
    let modal = null;
    let trap = null;
    let mounted = false;

    function query(selector) {
        return root ? root.querySelector(selector) : null;
    }

    function applyTheme(element) {
        const theme = config.theme || {};

        Object.keys(theme).forEach((property) => {
            element.style.setProperty(property, theme[property]);
        });
    }

    function selectedCategories() {
        const selected = [];

        root.querySelectorAll('[data-cc-category]').forEach((input) => {
            if (input.checked) {
                selected.push(input.getAttribute('data-cc-category'));
            }
        });

        return selected;
    }

    function syncSwitches(granted) {
        root.querySelectorAll('[data-cc-category]').forEach((input) => {
            const category = input.getAttribute('data-cc-category');

            if (category === 'necessary') {
                input.checked = true;
                return;
            }

            input.checked = granted.indexOf(category) !== -1;
        });
    }

    function bind(selector, handler) {
        root.querySelectorAll(selector).forEach((element) => element.addEventListener('click', (event) => {
            event.preventDefault();
            handler(event);
        }));
    }

    function mount() {
        if (mounted) {
            return true;
        }

        const template = document.getElementById('cc-template');

        if (!template || !document.body) {
            return false;
        }

        root = document.createElement('div');
        root.id = 'cc-root';
        applyTheme(root);
        root.appendChild(template.content.cloneNode(true));
        document.body.appendChild(root);

        banner = query('[data-cc="banner"]');
        modal = query('[data-cc="modal"]');
        trap = createFocusTrap(modal);

        bind('[data-cc="accept"]', () => actions.accept(OPTIONAL_CATEGORIES.slice()));
        bind('[data-cc="reject"]', () => actions.accept([]));
        bind('[data-cc="save"]', () => actions.accept(selectedCategories()));
        bind('[data-cc="manage"]', () => openModal());
        bind('[data-cc="close"]', () => closeModal());
        bind('[data-cc="backdrop"]', () => closeModal());

        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
            }
        });

        mounted = true;

        return true;
    }

    function showBanner() {
        if (!mount()) {
            return;
        }

        banner.hidden = false;
        document.documentElement.classList.add('cc-banner-open');
    }

    function hideBanner() {
        if (!banner) {
            return;
        }

        banner.hidden = true;
        document.documentElement.classList.remove('cc-banner-open');
    }

    function openModal() {
        if (!mount()) {
            return;
        }

        syncSwitches(actions.granted());
        banner.hidden = true;
        modal.hidden = false;
        document.documentElement.classList.add('cc-modal-open');
        trap.activate();
    }

    function closeModal() {
        if (!modal || modal.hidden) {
            return;
        }

        modal.hidden = true;
        document.documentElement.classList.remove('cc-modal-open');
        trap.deactivate();

        if (!actions.hasDecided()) {
            showBanner();
        }
    }

    function hideAll() {
        hideBanner();

        if (modal && !modal.hidden) {
            modal.hidden = true;
            document.documentElement.classList.remove('cc-modal-open');
            trap.deactivate();
        }
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest ? event.target.closest('[data-cookieconsent="show"]') : null;

        if (!trigger) {
            return;
        }

        event.preventDefault();
        openModal();
    });

    return { mount, showBanner, hideBanner, openModal, closeModal, hideAll };
}
