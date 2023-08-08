/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Carousel from './carousel';
import mobileImage1 from '../../images/onboarding-theme-small-mobile-1.webp';
import mobileImage2 from '../../images/onboarding-theme-small-mobile-2.webp';
import mobileImage3 from '../../images/onboarding-theme-small-mobile-3.webp';

/**
 * Theme step content for small screens.
 */
const SmallScreen = () => (
	<div className="sensei-setup-wizard-theme">
		<div className="sensei-setup-wizard-theme__learning-mode-carousel">
			<Carousel>
				<Carousel.Item>
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
				</Carousel.Item>
				<Carousel.Item>
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
				</Carousel.Item>
				<Carousel.Item>
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
				</Carousel.Item>
			</Carousel>
		</div>
	</div>
);

export default SmallScreen;
