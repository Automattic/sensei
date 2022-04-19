/* eslint-disable jest/no-done-callback */
/* eslint-disable-next-line jest/expect-expect */

/**
 * External dependencies
 */
const { test } = require( '@playwright/test' );
/**
 * Internal dependencies
 */
const { SenseiLMS } = require( '../pages/sensei-lms-page' );

test.describe( 'Students Management', () => {
	// test.beforeAll(() => {

	// })

	test( 'should allow me to add todo items', async ( { page } ) => {
		const sensei = new SenseiLMS( page );

		await sensei.gotoModule( 'Students' );
		return page.pause();
		// await page.locator( 'a:has-text("Students")' ).click();
		// await page.locator( '[aria-label="Select\\ an\\ action"]' ).click();
		// await page.locator( 'text=Add to Course' ).click();
	} );
} );
