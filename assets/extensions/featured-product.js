/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/*
 * Extensions featured product component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.title         Product title.
 * @param {string}   props.description   Product description.
 * @param {string}   props.badgeLabel    Badge label.
 * @param {string}   props.excerpt       Product excerpt.
 * @param {string}   props.image         Product image.
 * @param {string}   props.price         Product price.
 * @param {string}   props.buttonLink    CTA button link.
 * @param {Object}   props.htmlProps     Wrapper extra props.
 */
const FeaturedProduct = ( props ) => {
	const {
		title,
		description,
		badgeLabel,
		excerpt,
		image,
		price,
		buttonLink,
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
				'sensei-extensions__featured-product',
				htmlProps?.className
			) }
		>
			<section className="sensei-extensions__featured-product__column">
				<div className="sensei-extensions__featured-product__content">
					<header className="sensei-extensions__featured-product__header">
						<h2 className="sensei-extensions__featured-product__title">
							{ getProductText }
						</h2>

						{ badgeLabel && (
							<small className="sensei-extensions__featured-product__badge">
								{ badgeLabel }
							</small>
						) }
					</header>

					<div
						className="sensei-extensions__featured-product__description"
						dangerouslySetInnerHTML={ {
							__html: description,
						} }
					/>
				</div>
			</section>

			<section
				className="sensei-extensions__featured-product__column"
				style={ {
					backgroundImage,
				} }
			>
				<div className="sensei-extensions__featured-product__card">
					<h2 className="sensei-extensions__featured-product__card__title">
						{ title }
					</h2>

					<p className="sensei-extensions__featured-product__card__description">
						{ excerpt }
					</p>

					<div className="sensei-extensions__featured-product__card__price">
						{ sprintf(
							// translators: placeholder is the price.
							__( '%s USD / year (1 site)', 'sensei-lms' ),
							price
						) }
					</div>

					<a
						href={ buttonLink }
						target="_blank"
						rel="noreferrer external"
						className={ classnames(
							'sensei-extensions__featured-product__card__button',
							'components-button',
							'is-primary',
							'is-large'
						) }
					>
						{ getProductText }
					</a>
				</div>
			</section>
		</article>
	);
};

export default FeaturedProduct;
