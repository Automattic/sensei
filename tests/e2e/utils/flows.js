import { loginUser } from '@wordpress/e2e-test-utils';

const baseUrl = process.env.WP_BASE_URL;

const isSessionCookieSet = function( cookies ) {
	let result = false;

	if ( ! Array.isArray( cookies ) ) {
		return result;
	}

	cookies.forEach( ( cookie ) => {
		if ( cookie.name.startsWith( 'wordpress_logged_in' ) ) {
			result = true;
		}
	} );

	return result;
};

export const CommonFlow = {
	login: async ( username, password ) => {
		let retries = 0;

		/**
		 * loginUser uses internally page.waitForNavigation() to wait for the login flow to be completed. This is not
		 * enough which leads to the session cookie not being set in some of the cases, especially on travis. To
		 * overcome this problem, we retry 3 times to login correctly.
		 */
		do {
			retries++;
			await loginUser( username, password );
		} while (
			! isSessionCookieSet( await page.cookies() ) &&
			retries <= 3
		);

		expect( isSessionCookieSet( await page.cookies() ) ).toBe( true );
	},

	logout: async () => {
		// Log out link in admin bar is not visible so can't be clicked directly.
		const logoutLinks = await page.$$eval(
			'#wp-admin-bar-logout a',
			( am ) => am.filter( ( e ) => e.href ).map( ( e ) => e.href )
		);

		await page.goto( logoutLinks[ 0 ], {
			waitUntil: 'networkidle0',
		} );
	},

	clickAndNavigate: async ( selector ) => {
		await Promise.all( [
			await page.click( selector ),
			page.waitForNavigation( {
				waitUntil: 'networkidle2',
			} ),
		] );
	},

	clickXPathAndNavigate: async ( query ) => {
		const link = await page.$x( query );
		expect( link ).toHaveLength( 1 );

		await Promise.all( [
			link[ 0 ].click(),
			page.waitForNavigation( {
				waitUntil: 'networkidle2',
			} ),
		] );
	},

	clickLinkTextAndNavigate: async ( text, childElement = null ) => {
		const query = childElement
			? `//a/${ childElement }[contains(text(), "${ text }")]`
			: `//a[contains(text(), "${ text }")]`;

		await CommonFlow.clickXPathAndNavigate( query );
	},
};

export const AdminFlow = {
	login: async () => {
		await CommonFlow.login( 'admin', 'password' );
	},

	goToPlugins: async () => {
		await page.goto( baseUrl + '/wp-admin/plugins.php', {
			waitUntil: 'networkidle2',
		} );
	},
};
