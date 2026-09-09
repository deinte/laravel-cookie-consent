export function emit(name, detail) {
    if (typeof document === 'undefined') {
        return;
    }

    document.dispatchEvent(new CustomEvent(name, { detail, bubbles: false, cancelable: false }));
}
