/**
 * External dependencies
 */
import { execSync, exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify( exec );

/**
 * Run a WP CLI command.
 *
 * @param {string} command
 */
export const cli = ( command: string ): Buffer =>
	execSync( `npm run wp-env run tests-cli "${ command }"` );

export const cliAsync = async ( command: string ): Promise< string > => {
	const response = await execAsync(
		`npm run wp-env run tests-cli "${ command }"`
	);
	return response.stdout;
};
/**
 * Clean database.
 */
export const cleanAll = (): Buffer => {
	return execSync( 'npm run wp-env clean tests' );
};

/**
 * Configure site via WP CLI:
 * - Set permalink structure.
 */
export const configureSite = (): void => {
	[
		`rewrite structure /%postname%/`,
		`rewrite flush`,
		`theme activate course`,
	].forEach( cli );
};

/**
 * Change a Sensei setting via WP CLI.
 *
 * @todo Not working correctly, option is cached somewhere?
 * @param {string} name
 * @param {string} value
 */
export const updateSenseiSetting = ( name: string, value: string ): void => {
	cli( `wp eval \\"Sensei()->settings->set('${ name }', '${ value }');"` );
};
