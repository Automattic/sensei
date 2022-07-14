/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from '../../../extensions/store';
import senseiProUpsellImage from '../../../images/sensei-pro-upsell.png';
import CheckIcon from '../../../icons/checked.svg';

/**
 * Upgrade step during course creation wizard.
 */
const CourseUpgradeStep = () => {
	const { senseiProExtension } = useSelect(
		( select ) => ( {
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		} ),
		[]
	);

	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1 className="sensei-editor-wizard-step__title">
					{ __( 'Sell with Sensei Pro', 'sensei-lms' ) }
				</h1>
				<p className="sensei-editor-wizard-step__description">
					{ __(
						'Do you want to sell this course? This requires Sensei Pro which also unlocks many useful features.',
						'sensei-lms'
					) }
				</p>
				<strong className="sensei-editor-wizard-modal-upsell__price">
					{ sprintf(
						// translators: placeholder is the price.
						__( '%s USD', 'sensei-lms' ),
						senseiProExtension.price.replace( '.00', '' )
					) }
				</strong>
				<span className="sensei-editor-wizard-modal-upsell__price-detail">
					{ __( 'per year, 1 site', 'sensei-lms' ) }
				</span>
				<ul className="sensei-editor-wizard-modal-upsell__features">
					<FeatureItem>
						{ __( 'WooCommerce integration', 'sensei-lms' ) }
					</FeatureItem>
					<FeatureItem>
						{ __( "Schedule 'drip' content", 'sensei-lms' ) }
					</FeatureItem>
					<FeatureItem>
						{ __( 'Set expiration date of courses', 'sensei-lms' ) }
					</FeatureItem>
					<FeatureItem>
						{ __( 'Quiz timer', 'sensei-lms' ) }
					</FeatureItem>
					<FeatureItem>
						{ __(
							'Flashcards, Image Hotspots, and Checklists',
							'sensei-lms'
						) }
					</FeatureItem>
					<FeatureItem>
						{ __( '1 year of updates & support', 'sensei-lms' ) }
					</FeatureItem>
				</ul>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={ window.sensei.pluginUrl + senseiProUpsellImage }
					alt={ __(
						'Illustration of a course listing with the pricing defined and with the button "Purchase Button"',
						'sensei-lms'
					) }
					className="sensei-editor-wizard-modal__illustration-image"
				/>
			</div>
		</div>
	);
};

/**
 * Item of the feature list in the Upgrade Step
 *
 * @param {Object} props          Component Props
 * @param {string} props.children Text to be included after the feature item icon
 */
const FeatureItem = ( { children } ) => (
	<li className="sensei-editor-wizard-modal-upsell__feature-item">
		<CheckIcon className="sensei-editor-wizard-modal-upsell__feature-item-icon" />
		{ children }
	</li>
);

CourseUpgradeStep.Actions = ( { goToNextStep } ) => {
	const upgrade = () => {
		window.open(
			'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=course_editor_wizard',
			'sensei-pricing',
			'noreferrer'
		);
		goToNextStep();
	};
	return (
		<>
			<Button
				isTertiary
				onClick={ goToNextStep }
				className="sensei-editor-wizard-modal-upsell__button"
			>
				{ __( 'Continue with Sensei Free', 'sensei-lms' ) }
			</Button>
			<Button
				isPrimary
				onClick={ upgrade }
				target="_blank"
				className="sensei-editor-wizard-modal-upsell__button"
			>
				{ __( 'Get Sensei Pro', 'sensei-lms' ) }
			</Button>
		</>
	);
};

export default CourseUpgradeStep;
