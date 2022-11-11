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
import senseiProAdStarsUrl from '../../images/sensei-pro-ad-stars.png';
import { addUtms } from '../utils';

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
									'Better courses with Sensei Pro',
									'sensei-lms'
								) }
							</h2>
						</header>

						<div className="sensei-home__sensei-pro-ad__description">
							<p>
								{ __(
									'Get everything you need to sell courses and take your lessons to the next level.',
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
										'Sell courses with WooCommerce',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Schedule and drip courses and lessons',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Manage groups and cohorts',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Create interactive videos and lessons',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Add advanced quiz features',
										'sensei-lms'
									) }
								</li>
								<li>
									{ __(
										'Contact our experts for help',
										'sensei-lms'
									) }
								</li>
							</ul>

							<a
								href={ addUtms(
									'https://senseilms.com/checkout/?add-to-cart=7009'
								) }
								target="_blank"
								rel="noreferrer external"
								className="sensei-home__sensei-pro-ad__button is-primary is-large components-button"
							>
								{ __( 'Get Sensei Pro', 'sensei-lms' ) }
							</a>

							<a
								href={ addUtms(
									'https://senseilms.com/sensei-pro/'
								) }
								target="_blank"
								rel="noreferrer external"
								className="sensei-home__sensei-pro-ad__button is-secondary is-large components-button"
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
								'Photo of Gonzalo de la Campa smiling',
								'sensei-lms'
							) }
							className="sensei-home__sensei-pro-ad__card--image"
						/>
						<blockquote className="sensei-home__sensei-pro-ad__card--quote">
							{ __(
								'Thanks to Sensei Pro, I have been able to generate recurring income every month.',
								'sensei-lms'
							) }
						</blockquote>
						<div className="sensei-home__sensei-pro-ad__card--author">
							Gonzalo de la Campa |{ ' ' }
							{ __( 'WordPress Educator', 'sensei-lms' ) }
							<img
								src={
									window.sensei.pluginUrl +
									senseiProAdStarsUrl
								}
								alt={ __(
									'Image containing five stars, representing the rating of the plugin',
									'sensei-lms'
								) }
								className="sensei-home__sensei-pro-ad__card--stars"
							/>
						</div>
					</div>
				</section>
			</article>
		</Col>
	);
};

export default SenseiProAd;
