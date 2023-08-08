/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Section, H } from '../../shared/components/section';
import mainImage from '../../images/onboarding-theme-main.webp';
import mobileImage1 from '../../images/onboarding-theme-mobile-1.webp';
import mobileImage2 from '../../images/onboarding-theme-mobile-2.webp';
import mobileImage3 from '../../images/onboarding-theme-mobile-3.webp';
import mobileImage4 from '../../images/onboarding-theme-mobile-4.webp';
import mobileImage5 from '../../images/onboarding-theme-mobile-5.webp';
import quoteAuthorImage from '../../images/onboarding-theme-quote-author.webp';
import learningModeImage1 from '../../images/onboarding-theme-learning-mode-1.webp';
import learningModeImage2 from '../../images/onboarding-theme-learning-mode-2.webp';
import learningModeImage3 from '../../images/onboarding-theme-learning-mode-3.webp';
import Carousel from './carousel';

/**
 * Theme step content for big screens.
 */
const BigScreen = () => (
	<div className="sensei-setup-wizard-theme">
		<div className="sensei-setup-wizard-theme__main-image">
			<div className="sensei-setup-wizard-theme__image-wrapper">
				<img
					src={ window.sensei.pluginUrl + mainImage }
					alt={ __( 'Sensei theme illustration', 'sensei-lms' ) }
					className="sensei-setup-wizard-theme__image"
				/>
			</div>
		</div>

		<Section className="sensei-setup-wizard-theme__section">
			<H className="sensei-setup-wizard__subsection-title">
				{ __(
					'Mobile optimized so it looks great on any screen size',
					'sensei-lms'
				) }
			</H>

			<ul className="sensei-setup-wizard-theme__mobile-images">
				<li>
					<div className="sensei-setup-wizard-theme__image-wrapper">
						<img
							src={ window.sensei.pluginUrl + mobileImage1 }
							alt={ __(
								'Sensei theme illustration',
								'sensei-lms'
							) }
							className="sensei-setup-wizard-theme__image"
						/>
					</div>
				</li>
				<li>
					<div className="sensei-setup-wizard-theme__image-wrapper">
						<img
							src={ window.sensei.pluginUrl + mobileImage2 }
							alt={ __(
								'Sensei theme illustration',
								'sensei-lms'
							) }
							className="sensei-setup-wizard-theme__image"
						/>
					</div>
				</li>
				<li>
					<div className="sensei-setup-wizard-theme__image-wrapper">
						<img
							src={ window.sensei.pluginUrl + mobileImage3 }
							alt={ __(
								'Sensei theme illustration',
								'sensei-lms'
							) }
							className="sensei-setup-wizard-theme__image"
						/>
					</div>
				</li>
				<li>
					<div className="sensei-setup-wizard-theme__image-wrapper">
						<img
							src={ window.sensei.pluginUrl + mobileImage4 }
							alt={ __(
								'Sensei theme illustration',
								'sensei-lms'
							) }
							className="sensei-setup-wizard-theme__image"
						/>
					</div>
				</li>
				<li>
					<div className="sensei-setup-wizard-theme__image-wrapper">
						<img
							src={ window.sensei.pluginUrl + mobileImage5 }
							alt={ __(
								'Sensei theme illustration',
								'sensei-lms'
							) }
							className="sensei-setup-wizard-theme__image"
						/>
					</div>
				</li>
			</ul>
		</Section>

		<Section className="sensei-setup-wizard-theme__section">
			<figure className="sensei-setup-wizard-theme-testimonial">
				<img
					src={ window.sensei.pluginUrl + quoteAuthorImage }
					alt={ __( 'Sensei theme illustration', 'sensei-lms' ) }
					className="sensei-setup-wizard-theme-testimonial__image"
				/>
				<div className="sensei-setup-wizard-theme-testimonial__content">
					<blockquote className="sensei-setup-wizard-theme-testimonial__quote">
						<p>
							{ __(
								'I always wanted to write, and thanks to Course, I got it right. My writing is clearer, and I can finally get my message across.',
								'sensei-lms'
							) }
						</p>
					</blockquote>
					<figcaption>
						<strong className="sensei-setup-wizard-theme-testimonial__author">
							Cristopher Brown
						</strong>
						{ __(
							'Founder at BeautifulWriting.com',
							'sensei-lms'
						) }
					</figcaption>
				</div>
			</figure>
		</Section>

		<Section className="sensei-setup-wizard-theme__section">
			<H className="sensei-setup-wizard__subsection-title">
				{ __(
					'All new and improved Learning Mode to help keep your students focused',
					'sensei-lms'
				) }
			</H>

			<div className="sensei-setup-wizard-theme__learning-mode-carousel">
				<Carousel>
					<Carousel.Item>
						<div className="sensei-setup-wizard-theme__image-wrapper">
							<img
								src={
									window.sensei.pluginUrl + learningModeImage1
								}
								alt={ __(
									'Sensei theme illustration',
									'sensei-lms'
								) }
								className="sensei-setup-wizard-theme__image"
							/>
						</div>
					</Carousel.Item>
					<Carousel.Item>
						<div className="sensei-setup-wizard-theme__image-wrapper">
							<img
								src={
									window.sensei.pluginUrl + learningModeImage2
								}
								alt={ __(
									'Sensei theme illustration',
									'sensei-lms'
								) }
								className="sensei-setup-wizard-theme__image"
							/>
						</div>
					</Carousel.Item>
					<Carousel.Item>
						<div className="sensei-setup-wizard-theme__image-wrapper">
							<img
								src={
									window.sensei.pluginUrl + learningModeImage3
								}
								alt={ __(
									'Sensei theme illustration',
									'sensei-lms'
								) }
								className="sensei-setup-wizard-theme__image"
							/>
						</div>
					</Carousel.Item>
				</Carousel>
			</div>
		</Section>
	</div>
);

export default BigScreen;
