/**
 * Mock window.location.search value.
 *
 * @param {string} search Search string to mock.
 */
export const mockSearch = ( search ) => {
	Object.defineProperty( window, 'location', {
		value: {
			search,
		},
		writable: true,
	} );
};
