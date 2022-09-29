/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from '../../extensions/store';
import { Col } from '../grid';

/**
 * Extensions featured product component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.title       Product title.
 * @param {string} props.description Product description.
 * @param {Array}  props.features    Product features.
 * @param {string} props.badgeLabel  Badge label.
 * @param {string} props.excerpt     Product excerpt.
 * @param {string} props.image       Product image.
 * @param {string} props.price       Product price.
 * @param {string} props.buttonLink  CTA button link.
 * @param {string} props.buttonTitle CTA button title.
 * @param {Object} props.htmlProps   Wrapper extra props.
 */
const FeaturedProduct = ( props ) => {
	const {
		title,
		description,
		features,
		badgeLabel,
		excerpt,
		image,
		price,
		buttonLink,
		buttonTitle,
		htmlProps,
	} = props;

	const backgroundImage = image && `url(${ image })`;
	const getProductText = sprintf(
		// translators: placeholder is the product title.
		__( 'Get %s', 'sensei-lms' ),
		title
	);

	return (
		<article
			{ ...htmlProps }
			className={ classnames(
				'sensei-home__featured-product',
				htmlProps?.className
			) }
		>
			<section className="sensei-home__featured-product__column">
				<div className="sensei-home__featured-product__content">
					<header className="sensei-home__featured-product__header">
						<h2 className="sensei-home__featured-product__title">
							{ getProductText }
						</h2>

						{ badgeLabel && (
							<small className="sensei-home__featured-product__badge">
								{ badgeLabel }
							</small>
						) }
					</header>

					<div className="sensei-home__featured-product__description">
						<p>{ description }</p>

						{ features && (
							<ul>
								{ features.map( ( feature, key ) => (
									<li key={ key }>{ feature }</li>
								) ) }
							</ul>
						) }
					</div>
				</div>
			</section>

			<section
				className="sensei-home__featured-product__column"
				style={ {
					backgroundImage,
				} }
			>
				<div className="sensei-home__featured-product__card">
					<h2 className="sensei-home__featured-product__card__title">
						{ title }
					</h2>

					<p className="sensei-home__featured-product__card__description">
						{ excerpt }
					</p>

					<div className="sensei-home__featured-product__card__price">
						{ price }
					</div>

					<a
						href={ buttonLink }
						target="_blank"
						rel="noreferrer external"
						className={ classnames(
							'sensei-home__featured-product__card__button',
							'components-button',
							'is-primary',
							'is-large'
						) }
					>
						{ buttonTitle }
					</a>
				</div>
			</section>
		</article>
	);
};

/*
 * Sensei Pro featured product component.
 */
const SenseiProAd = () => {
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
		<Col as="section" className="sensei-home__section" cols={ 12 }>
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
		</Col>
	);
};

export default SenseiProAd;
