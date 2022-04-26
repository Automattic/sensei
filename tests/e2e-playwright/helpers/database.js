/**
 * External dependencies
 */
const util = require( 'util' );
const childProcess = require( 'child_process' );
const exec = util.promisify( childProcess.exec );
/**
 * Clean database env
 *
 */
module.exports = {
	cleanAll: async () => {
		const { stdout } = await exec( 'npm run wp-env clean all' );

		return stdout;
	},
};
