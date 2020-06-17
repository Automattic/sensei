import { sprintf, __ } from '@wordpress/i18n';

export const getFeatureObservation = ( slug, selectedFeatures ) => {
	if ( 'woocommerce' !== slug || ! selectedFeatures ) {
		return null;
	}

	const titles = selectedFeatures
		.filter( ( feature ) => feature.wccom_product_id )
		.map( ( feature ) => feature.rawTitle )
		.join( __( ' and ', 'sensei-lms' ) );

	return sprintf(
		// translators: Placeholder is the plugin titles.
		__(
			'* WooCommerce is required to receive updates for %1$s. Once WooCommerce is installed, you will be taken to WooCommerce.com to complete the purchase process.',
			'sensei-lms'
		),
		titles
	);
};
