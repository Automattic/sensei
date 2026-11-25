/**
 * Get the Sensei Pro upgrade URL.
 *
 * @param {string} campaign The campaign name.
 *
 * @return {string} The upgrade URL.
 */
export const getSenseiProUpsellUrl = ( campaign = '' ) => {
	const { upsellUrl } = window.sensei_admin;
	const url = new URL( upsellUrl );

	url.searchParams.set( 'utm_source', 'plugin_sensei' );
	url.searchParams.set( 'utm_medium', 'upsell' );
	url.searchParams.set( 'utm_campaign', campaign );

	return url.toString();
};

/**
 * Get the Sensei Pro checkout URL.
 *
 * @param {string} campaign The campaign name.
 *
 * @return {string} The checkout URL.
 */
export const getSenseiProCheckoutUrl = ( campaign = '' ) => {
	const { checkoutUrl } = window.sensei_admin;
	const url = new URL( checkoutUrl );

	url.searchParams.set( 'utm_source', 'plugin_sensei' );
	url.searchParams.set( 'utm_medium', 'checkout' );
	url.searchParams.set( 'utm_campaign', campaign );

	return url.toString();
};
