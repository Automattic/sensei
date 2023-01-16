/**
 * Add UTM codes to url for SenseiLMS.com.
 *
 * @param {string} url
 * @return {string} The url with UTMs added.
 */
export const addUtms = ( url ) => {
	try {
		const parsed = new URL( url );
		if (
			parsed.hostname === 'senseilms.com' &&
			! parsed.searchParams.has( 'utm_source' ) &&
			! parsed.searchParams.has( 'utm_medium' ) &&
			! parsed.searchParams.has( 'utm_campaign' )
		) {
			parsed.searchParams.set( 'utm_source', 'plugin_sensei' );
			parsed.searchParams.set( 'utm_medium', 'upsell' );
			parsed.searchParams.set( 'utm_campaign', 'sensei_home' );
		}
		return parsed.toString();
	} catch {
		return url;
	}
};

/**
 * Return hostname for a given URL.
 *
 * @param {string} url URL to parse.
 * @return {string} The hostname for the given URL.
 */
const getHostname = ( url ) => {
	const element = document.createElement( 'a' );
	element.href = url;
	return element.hostname;
};

/**
 * Verifies if a given URL is external or not.
 *
 * @param {string} url The URL to analyze.
 * @return {boolean} If it's external or not.
 */
export const isUrlExternal = ( url ) => {
	return getHostname( window.location ) !== getHostname( url );
};
