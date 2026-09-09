export const CATEGORIES = ['necessary', 'preferences', 'statistics', 'marketing'];
export const OPTIONAL_CATEGORIES = CATEGORIES.filter((category) => category !== 'necessary');
export const SCRIPT_SELECTOR = 'script[type="text/plain"][data-cookieconsent]';
export const EMBED_SELECTOR = '[data-cc-src][data-cookieconsent]';
export const TRANSPARENT_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
