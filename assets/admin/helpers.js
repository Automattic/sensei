/**
 * Get the Sensei Pro upgrade URL.
 *
 * @param {string} campaign The campaign name.
 *
 * @return {string} The upgrade URL.
 */
export const getSenseiProUpsellUrl = ( campaign = '' ) => {
	const { upsellUrl } = window.sensei_admin;

	const sensieParams = new URLSearchParams( {
		utm_source: 'plugin_sensei',
		utm_medium: 'upsell',
		utm_campaign: campaign,
	} );

	return `${ upsellUrl }?${ sensieParams.toString() }`;
};
