/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import FeaturedProduct from './featured-product';
import { EXTENSIONS_STORE } from './store';

/*
 * Sensei Pro featured product component.
 */
const FeaturedProductSenseiPro = () => {
	const { senseiProExtension } = useSelect(
		( select ) => ( {
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		} ),
		[]
	);

	if ( ! senseiProExtension || senseiProExtension.is_installed === true ) {
		return <></>;
	}

	return (
		<FeaturedProduct
			title={ senseiProExtension.title }
			excerpt={ senseiProExtension.excerpt }
			description={ __(
				'By upgrading to Sensei Pro, you get all the great features found in Sensei LMS plus:',
				'sensei-lms'
			) }
			features={ [
				__( 'WooCommerce integration', 'sensei-lms' ),
				__( 'Schedule ‘drip’ content', 'sensei-lms' ),
				__( 'Set expiration date of courses', 'sensei-lms' ),
				__( 'Advanced quiz features', 'sensei-lms' ),
				__(
					'Flashcard, image hotspot, and tasklist blocks',
					'sensei-lms'
				),
				__( 'Premium support', 'sensei-lms' ),
			] }
			image={ senseiProExtension.image_large }
			badgeLabel={ __( 'new', 'sensei-lms' ) }
			price={ sprintf(
				// translators: placeholder is the price.
				__( '%s USD / year (1 site)', 'sensei-lms' ),
				senseiProExtension.price
			) }
			buttonLink="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=extensions_header"
			buttonTitle={ __( 'Learn More', 'sensei-lms' ) }
		/>
	);
};

export default FeaturedProductSenseiPro;
