/*
 * External dependencies.
 */
import util from 'util';
import childProcess from 'child_process';

const exec = util.promisify( childProcess.exec );

/**
 * Run a wp cli command.
 *
 * @param {string} command The command with arguments.
 */
export async function wpcli( command ) {
	const { stdout } = await exec(
		`${ __dirname }/../bin/wp-cli.sh ${ command }`
	);
	return stdout;
}

export async function resetSetupWizard() {
	await wpcli( 'option delete sensei_setup_wizard_data' );
	try {
		await wpcli( 'plugin uninstall --deactivate sensei-certificates' );
	} catch ( err ) {}
}

export function cleanupSenseiData() {
	const code = `
		require 'wp-content/plugins/sensei/includes/class-sensei-data-cleaner.php';
		Sensei_Data_Cleaner::cleanup_all();
	`;
	return wpcli( `eval "${ code }"` );
}

export function siteUrl( url ) {
	const baseUrl = process.env.WP_BASE_URL;
	return [ baseUrl, url ].join( '/' );
}

export function adminUrl( url ) {
	const baseUrl = process.env.WP_BASE_URL;
	return [ baseUrl, 'wp-admin', url ].join( '/' );
}
