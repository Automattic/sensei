import { AdminFlow } from '../../utils/flows';

describe( 'Admin can login and make sure Sensei LMS is activated', () => {
	it( 'Should login', async () => {
		await AdminFlow.login();
		const adminMenuMain = await page.waitForSelector( '#adminmenumain' );

		expect( adminMenuMain ).toBeTruthy();
	} );

	it( 'Should make sure Sensei LMS is activated or activate it', async () => {
		const slug = 'sensei-lms';
		let deactivateLink;

		await AdminFlow.goToPlugins();

		deactivateLink = await page.$(
			`tr[data-slug="${ slug }"] .deactivate a`
		);
		if ( deactivateLink ) {
			return;
		}

		await page.click( `tr[data-slug="${ slug }"] .activate a` );

		// Go to the plugins page again to make sure it's in the plugin page.
		// It can be the setup wizard.
		await AdminFlow.goToPlugins();
		deactivateLink = await page.waitForSelector(
			`tr[data-slug="${ slug }"] .deactivate a`
		);

		expect( deactivateLink ).toBeTruthy();
	} );
} );
