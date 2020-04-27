export const mockSearch = ( search ) => {
	Object.defineProperty( window, 'location', {
		value: {
			search,
		},
		writable: true,
	} );
};
