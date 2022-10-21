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
import senseiProAdImageUrl from '../../images/sensei-pro-ad-image.png';
import SenseiProAdCTA from '../../images/sensei-pro-ad-cta.svg';

/**
 * Sensei Pro Ad to be shown on Sensei Home.
 *
 * @param {boolean} show Whether to show or not the ad.
 */
const SenseiProAd = ( { show } ) => {
	const { senseiProExtension } = useSelect(
		( select ) => ( {
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		} ),
		[]
	);

	if ( ! senseiProExtension || ! show ) {
		return null;
	}

	return (
		<Col as="section" className="sensei-home__section" cols={ 12 }>
			<article className="sensei-home__sensei-pro-ad">
				<section className="sensei-home__sensei-pro-ad__column">
					<div className="sensei-home__sensei-pro-ad__content">
						<header className="sensei-home__sensei-pro-ad__header">
							<h2 className="sensei-home__sensei-pro-ad__title">
								{ __(
									'Start selling with Sensei Pro',
									'sensei-lms'
								) }
							</h2>
						</header>

						<div className="sensei-home__sensei-pro-ad__description">
							<p>
								{ __(
									'All the features in one package made so that you can start selling your courses right away.',
									'sensei-lms'
								) }
							</p>

							<div className="sensei-home__sensei-pro-ad__price">
								<h3 className="sensei-home__sensei-pro-ad__price__title">
									{ sprintf(
										// translators: placeholder is the price.
										__( '%s USD', 'sensei-lms' ),
										senseiProExtension.price.replace(
											'.00',
											''
										)
									) }
								</h3>
								<p>
									{ __( 'per year, 1 site', 'sensei-lms' ) }
								</p>
							</div>

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

							<a
								href="https://senseilms.com/checkout/?add-to-cart=7009"
								target="_blank"
								rel="noreferrer external"
								className="sensei-home__sensei-pro-ad__button is-primary is-large components-button"
							>
								{ __( 'Get Sensei Pro', 'sensei-lms' ) }
							</a>

							<a
								href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=extensions_header"
								target="_blank"
								rel="noreferrer external"
								className="sensei-home__sensei-pro-ad__button is-secondary is-large components-button "
							>
								{ __( 'Learn More', 'sensei-lms' ) }
							</a>
						</div>
					</div>
				</section>

				<section
					className="sensei-home__sensei-pro-ad__column"
					aria-hidden="true"
				>
					<div className="sensei-home__sensei-pro-ad__card">
						<img
							src={
								window.sensei.pluginUrl + senseiProAdImageUrl
							}
							alt={ __(
								'Image in black and white of a man looking at a microphone',
								'sensei-lms'
							) }
							className="sensei-home__sensei-pro-ad__card--image"
						/>
						<div className="sensei-home__sensei-pro-ad__card--price">
							$29.99
						</div>
						<SenseiProAdCTA className="sensei-home__sensei-pro-ad__card--cta" />
					</div>
				</section>
			</article>
		</Col>
	);
};

export default SenseiProAd;
