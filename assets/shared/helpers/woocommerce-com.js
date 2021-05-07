/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Get WooCommerce.com checkout URL for the given plugins
 *
 * @param {Array}  features  List of features to be installed.
 * @param {Object} wccomData Woocommerce.com connect parameters
 *
 * @return {string} The checkout URL
 */
export const getWoocommerceComPurchaseUrl = ( features, wccomData ) => {
	return addQueryArgs( 'https://woocommerce.com/cart', {
		'wccom-replace-with': features.map( getWccomProductId ).join( ',' ),
		...( wccomData || {} ),
	} );
};

/**
 * Get the WooCommerce.com product ID for the feature.
 *
 * @param {Object} feature The feature.
 *
 * @return {string} The product ID.
 */
export const getWccomProductId = ( feature ) => feature.wccom_product_id;
