/**
 * External dependencies
 */
const path = require( 'path' );
const CONTEXT_DIR = path.resolve( __dirname, '../contexts' );

/**
 * @typedef {"admin"} UserRole
 */
/**
 * Returns the browser context by user role. E.g "Admin", "Subscriber"
 *
 * @param {userRole} userRole
 */
const getContextByRole = ( userRole ) =>
	path.resolve( CONTEXT_DIR, `${ userRole }.json` );

module.exports = {
	getContextByRole,
};
