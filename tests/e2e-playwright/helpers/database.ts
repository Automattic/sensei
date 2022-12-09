/**
 * External dependencies
 */
import { execSync } from 'child_process';

/**
 * Run a WP CLI command.
 *
 * @param {string} command
 */
export const cli = ( command ) => execSync( `npm run wp-env run tests-cli "${ command }"` );

/**
 * Clean database.
 */
export const cleanAll = () => {
	return execSync( 'npm run wp-env clean tests' );
};

/**
 * Configure site via WP CLI:
 * - Set permalink structure.
 */
export const configureSite = () => {
	[ `wp rewrite structure /%postname%/`, `wp rewrite flush` ].forEach( cli );
};

/**
 * Change a Sensei setting via WP CLI.
 *
 * @todo Not working correctly, option is cached somewhere?
 *
 * @param {string} name
 * @param {string} value
 */
export const updateSenseiSetting = ( name, value ) => {
	cli( `wp eval \\"Sensei()->settings->set('${ name }', '${ value }');"` );
};
