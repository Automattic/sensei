/**
 * Mock window.location.search value.
 *
 * `window.location` is an unforgeable, non-configurable property in jsdom, so
 * the search string is set through the history API instead of redefining it.
 *
 * @param {string} search Search string to mock.
 */
export const mockSearch = ( search ) => {
	window.history.replaceState( {}, '', `?${ search }` );
};
