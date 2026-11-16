/**
 * Get the Sensei Pro upgrade URL.
 *
 * @param {string} campaign The campaign name.
 *
 * @return {string} The upgrade URL.
 */
export const getSenseiProUpsellUrl = ( campaign = '' ) => {
	const { upsellUrl } = window.sensei_admin;

	const senseiParams = new URLSearchParams( {
		utm_source: 'plugin_sensei',
		utm_medium: 'upsell',
		utm_campaign: campaign,
	} );

	return `${ upsellUrl }?${ senseiParams.toString() }`;
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
