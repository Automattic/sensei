/**
 * Builds a `@wordpress/data` mock that replaces only the named exports.
 *
 * A bare `jest.mock( '@wordpress/data' )` automocks every export, so
 * `createReduxStore` returns undefined. `@wordpress/blocks` builds and unlocks
 * its store when the module loads, so it then throws "Cannot unlock an
 * undefined object" before any test runs.
 *
 * The remaining exports are read through a proxy rather than spread, because
 * spreading evaluates the package's lazy getters and re-enters its own
 * circular imports.
 *
 * @param {Object} mocks Exports to replace, keyed by export name.
 * @return {Proxy} The mocked module.
 */
module.exports = ( mocks = {} ) => {
	const actual = jest.requireActual( '@wordpress/data' );

	return new Proxy( actual, {
		get: ( target, prop ) =>
			prop in mocks ? mocks[ prop ] : target[ prop ],
	} );
};
