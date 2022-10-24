/**
 * Add UTM codes to url for SenseiLMS.com.
 *
 * @param {string} url
 * @return {string} The url with UTMs added.
 */
export const addUtms = ( url ) => {
	const parsed = new URL( url );
	if ( parsed.hostname === 'senseilms.com' ) {
		parsed.searchParams.set( 'utm_source', 'plugin_sensei' );
		parsed.searchParams.set( 'utm_medium', 'upsell' );
		parsed.searchParams.set( 'utm_campaign', 'sensei_home' );
	}
	return parsed.toString();
};
