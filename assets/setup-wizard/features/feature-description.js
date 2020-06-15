import { __ } from '@wordpress/i18n';
import { logLink } from '../log-event';

/**
 * Feature description component
 *
 * @param {Object} props
 * @param {string} props.slug    Feature slug.
 * @param {string} props.excerpt Feature excerpt.
 * @param {string} [props.link]  Feature link.
 */
const FeatureDescription = ( { slug, excerpt, link } ) => (
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
		{ 'woocommerce' === slug && (
			<span className="sensei-setup-wizard__woocommerce-observation">
				{ __(
					'* WooCommerce is required to receive updates for Sensei Content Drip and WooCommerce Paid Courses. Once WooCommerce is installed, you will be taken to WooCommerce.com to complete the purchase process.',
					'sensei-lms'
				) }
			</span>
		) }
	</>
);

export default FeatureDescription;
