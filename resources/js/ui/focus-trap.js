const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

export function createFocusTrap(container) {
    let previouslyFocused = null;

    function focusables() {
        return Array.prototype.filter.call(container.querySelectorAll(FOCUSABLE), (element) => element.offsetParent !== null || element === document.activeElement);
    }

    function onKeydown(event) {
        if (event.key !== 'Tab') {
            return;
        }

        const items = focusables();

        if (items.length === 0) {
            event.preventDefault();
            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
            return;
        }

        if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    return {
        activate() {
            previouslyFocused = document.activeElement;
            container.addEventListener('keydown', onKeydown);

            const items = focusables();

            if (items.length > 0) {
                items[0].focus();
            }
        },
        deactivate() {
            container.removeEventListener('keydown', onKeydown);

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }

            previouslyFocused = null;
        },
    };
}
