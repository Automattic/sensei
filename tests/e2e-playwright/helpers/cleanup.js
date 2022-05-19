/**
 * External dependencies
 */
const util = require( 'node:util' );
const childProcess = require( 'node:child_process' );
const exec = util.promisify( childProcess.exec );

/**
 * Run a wp cli command.
 *
 * @param {string} command The command with arguments.
 */
async function wpcli( command ) {
	const { stdout } = await exec( `npm run wp-env run cli "${ command }"` );
	return stdout;
}

function resetSetupWizard() {
	return wpcli( 'option delete sensei_setup_wizard_data' );
}

function cleanupSenseiData() {
	const code = `wp eval 'require \\"wp-content/plugins/sensei/includes/class-sensei-data-cleaner.php\\"; Sensei_Data_Cleaner::cleanup_all();'`;
	return wpcli( code );
}
function adminUrl( url ) {
	const baseUrl = process.env.WP_BASE_URL;
	return [ baseUrl, 'wp-admin', url ].join( '/' );
}

module.exports = {
	resetSetupWizard,
	cleanupSenseiData,
	adminUrl,
};
