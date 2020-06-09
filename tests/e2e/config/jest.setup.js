const path = require( 'path' );
const mkdirp = require( 'mkdirp' );

/**
 * Jest puppeteer does not support taking a screenshot on test failure atm. The below snippet is based on a solution
 * proposed here: https://github.com/smooth-code/jest-puppeteer/issues/131
 */
const screenshotsPath = path.resolve( __dirname, '../screenshots' );

const takeScreenshot = ( testName, pageInstance = page ) => {
	mkdirp.sync( screenshotsPath );

	const fileName = `${ new Date().toISOString() }_${ testName }.png`;
	fileName.replace( /[^a-z0-9.-]+/gi, '_' );

	const filePath = path.join( screenshotsPath, fileName );

	return pageInstance.screenshot( { path: filePath, fullPage: true } );
};

/**
 * jasmine reporter does not support async.
 * So we store the screenshot promise and wait for it before each test
 */
let screenshotPromise = Promise.resolve();
beforeEach( () => screenshotPromise );
afterAll( () => screenshotPromise );

/**
 * Take a screenshot on Failed test.
 * Jest standard reporters run in a separate process so they don't have
 * access to the page instance. Using jasmine reporter allows us to
 * have access to the test result, test name and page instance at the same time.
 */
// eslint-disable-next-line jest/no-jasmine-globals
jasmine.getEnv().addReporter( {
	specDone: ( result ) => {
		if ( result.status === 'failed' ) {
			screenshotPromise = takeScreenshot( result.fullName );
		}
	},
} );
