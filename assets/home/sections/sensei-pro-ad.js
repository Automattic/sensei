/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from '../../extensions/store';
import { Col } from '../grid';

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

	if (
		! senseiProExtension /* || senseiProExtension.is_installed === true */
	) {
		return null;
	}

	return (
		<Col as="section" className="sensei-home__section" cols={ 12 }>
			<article className="sensei-home__featured-product">
				<section className="sensei-home__featured-product__column">
					<div className="sensei-home__featured-product__content">
						<header className="sensei-home__featured-product__header">
							<h2 className="sensei-home__featured-product__title">
								{ __(
									'Start selling with Sensei Pro',
									'sensei-lms'
								) }
							</h2>
						</header>

						<div className="sensei-home__featured-product__description">
							<p>
								{ __(
									'All the features in one package made so that you can start selling your courses right away.',
									'sensei-lms'
								) }
							</p>

							<ul>
								<li>
									{ __(
										'WooCommerce integration',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Schedule ‘drip’ content',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Set expiration date of courses',
										'sensei-lms'
									) }
								</li>
								<li>{ __( 'Quiz timer', 'sensei-lms' ) }</li>
								<li>
									{ __(
										'Flashcards, Image Hotspots, Checklists and Interactive Video',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'1 year of updates & support',
										'sensei-lms'
									) }
								</li>
							</ul>
						</div>
					</div>
				</section>

				<section
					className="sensei-home__featured-product__column"
					style={ {
						backgroundImage: `url(${ senseiProExtension.image_large })`,
					} }
				>
					<div className="sensei-home__featured-product__card">
						<h2 className="sensei-home__featured-product__card__title">
							{ senseiProExtension.title }
						</h2>

						<p className="sensei-home__featured-product__card__description">
							{ senseiProExtension.excerpt }
						</p>

						<div className="sensei-home__featured-product__card__price">
							{ sprintf(
								// translators: placeholder is the price.
								__( '%s USD / year (1 site)', 'sensei-lms' ),
								senseiProExtension.price
							) }
						</div>

						<a
							href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=extensions_header"
							target="_blank"
							rel="noreferrer external"
							className="sensei-home__featured-product__card__button components-button is-primary is-large"
						>
							{ __( 'Learn More', 'sensei-lms' ) }
						</a>
					</div>
				</section>
			</article>
		</Col>
	);
};

export default SenseiProAd;
