/**
 * Internal dependencies
 */
import { getSenseiProCheckoutUrl } from './helpers';

describe( 'getSenseiProCheckoutUrl', () => {
	const originalSenseiAdmin = window.sensei_admin;

	afterEach( () => {
		if ( originalSenseiAdmin === undefined ) {
			delete window.sensei_admin;
		} else {
			window.sensei_admin = originalSenseiAdmin;
		}
	} );

	it.each( [
		[
			'self-hosted',
			'https://senseilms.com/checkout/?add-to-cart=7009',
			'https://senseilms.com/checkout/?add-to-cart=7009&utm_source=plugin_sensei&utm_medium=checkout&utm_campaign=home',
		],
		[
			'WordPress.com',
			'https://wordpress.com/checkout/example.wordpress.com/sensei_pro_monthly',
			'https://wordpress.com/checkout/example.wordpress.com/sensei_pro_monthly?utm_source=plugin_sensei&utm_medium=checkout&utm_campaign=home',
		],
	] )(
		'adds campaign tracking to %s checkout',
		( _host, checkoutUrl, expected ) => {
			window.sensei_admin = { checkoutUrl };

			const url = getSenseiProCheckoutUrl( 'home' );

			expect( url ).toBe( expected );
		}
	);
} );
