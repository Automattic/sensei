import { sprintf, __ } from '@wordpress/i18n';
import { logLink } from '../log-event';

/**
 * @typedef  {Object} Feature
 * @property {string} rawTitle Feature title.
 */
/**
 * Feature description component
 *
 * @param {Object}    props
 * @param {string}    props.slug               Feature slug.
 * @param {string}    props.excerpt            Feature excerpt.
 * @param {string}    [props.link]             Feature link.
 * @param {Feature[]} [props.selectedFeatures] Features list.
 */
const FeatureDescription = ( { slug, excerpt, link, selectedFeatures } ) => {
	let wcObservation = null;

	if ( 'woocommerce' === slug && selectedFeatures ) {
		const titles = selectedFeatures
			.filter( ( feature ) => feature.wccom_product_id )
			.map( ( feature ) => feature.rawTitle )
			.join( __( ' and ', 'sensei-lms' ) );

		wcObservation = sprintf(
			// translators: Placeholder is the plugin titles.
			__(
				'* WooCommerce is required to receive updates for %1$s. Once WooCommerce is installed, you will be taken to WooCommerce.com to complete the purchase process.',
				'sensei-lms'
			),
			titles
		);
	}

	return (
		<>
			{ excerpt }
			{ link && (
				<>
					{ ' ' }
					<a
						className="sensei-setup-wizard__learn-more link__color-primary"
						href={ link }
						target="_blank"
						rel="noopener noreferrer"
						{ ...logLink( 'setup_wizard_features_learn_more', {
							slug,
						} ) }
					>
						{ __( 'Learn more', 'sensei-lms' ) }
					</a>
				</>
			) }
			{ wcObservation && (
				<em className="sensei-setup-wizard__woocommerce-observation">
					{ wcObservation }
				</em>
			) }
		</>
	);
};

export default FeatureDescription;
