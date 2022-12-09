/**
 * External dependencies
 */
import { Browser } from '@playwright/test';

/**
 * External dependencies
 */
const path = require( 'path' );
const CONTEXT_DIR = path.resolve( __dirname, '../contexts' );

/**
 * @typedef {"admin"} UserRole
 */
/**
 * Returns the browser context by user role. E.g "admin", "student"
 *
 * @param {string} userRole
 */
export const getContextByRole = ( userRole ) =>
	path.resolve( CONTEXT_DIR, `${ userRole }.json` );

/**
 * Execute the function as an admin.
 *
 * @param {Browser} browser
 * @param {Function} fn Callback.
 * @return {Promise<*>} Callback return value.
 */
export const asAdmin = async ( { browser }, fn ) => {
	const context = await browser.newContext( adminRole() );
	return fn( { context } );
};

export const studentRole = () => ( {
	storageState: getContextByRole( 'student' ),
} );
export const adminRole = () => ( {
	storageState: getContextByRole( 'admin' ),
} );
