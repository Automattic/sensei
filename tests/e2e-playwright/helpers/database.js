/**
 * External dependencies
 */
const util = require( 'util' );
const childProcess = require( 'child_process' );
const exec = util.promisify( childProcess.exec );
/**
 * Clean database tests env.
 *
 */
const cleanAll = async () => {
	const { stdout, stderr } = await exec( 'npm run wp-env clean tests' );
	process.stdout.write( stdout );
	process.stderr.write( stderr );
	return { stdout, stderr };
};
module.exports = {
	cleanAll,
};
