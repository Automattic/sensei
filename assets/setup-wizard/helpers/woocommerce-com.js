/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

export const getWoocommerceComPurchaseUrl = ( productIds, wccomData ) => {
	return addQueryArgs( 'https://woocommerce.com/cart', {
		'wccom-replace-with': productIds.join( ',' ),
		...wccomData,
	} );
};
