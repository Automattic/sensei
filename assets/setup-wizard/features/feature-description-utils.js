/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWccomProductId } from '../../shared/helpers/woocommerce-com';

/**
 * @typedef  {Object} Feature
 * @property {string} rawTitle Feature raw title.
 */
/**
 * Get a custom observation for a feature.
 *
 * @param {string}    slug             Feature slug.
 * @param {Feature[]} selectedFeatures Features list.
 *
 * @return {string|null} Feature observation.
 */
export const getFeatureObservation = ( slug, selectedFeatures ) => {
	if ( 'woocommerce' !== slug || ! selectedFeatures ) {
		return null;
	}

	const titles = selectedFeatures
		.filter( getWccomProductId )
		.map( ( feature ) => feature.rawTitle )
		.join( __( ' and ', 'sensei-lms' ) );

	return sprintf(
		// translators: Placeholder is the plugin titles.
		__(
			'* WooCommerce is required to receive updates for %1$s.',
			'sensei-lms'
		),
		titles
	);
};
