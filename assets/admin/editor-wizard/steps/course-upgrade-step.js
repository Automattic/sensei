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

/**
 * Upgrade step during course creation wizard.
 */
const CourseUpgradeStep = () => {
	const { senseiProExtension } = useSelect( ( select ) => {
		return {
			senseiProExtension: select(
				EXTENSIONS_STORE
			).getSenseiProExtension(),
		};
	} );

	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1>{ __( 'Sell with Sensei Pro', 'sensei-lms' ) }</h1>
				<p>
					{ __(
						'Do you want to sell this course? This requires Sensei Pro which also unlocks many useful features.',
						'sensei-lms'
					) }
				</p>
				<h1 className="sensei-editor-wizard-modal-upsell__price">
					{ sprintf(
						// translators: placeholder is the price.
						__( '%s USD', 'sensei-lms' ),
						senseiProExtension.price.replace( '.00', '' )
					) }
				</h1>
				<p className="sensei-editor-wizard-modal-upsell__price-detail">
					{ __( 'per year, 1 site', 'sensei-lms' ) }
				</p>
				<ul className="sensei-editor-wizard-modal-upsell__features">
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __( 'WooCommerce integration', 'sensei-lms' ) }
					</li>
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __( "Schedule 'drip' content", 'sensei-lms' ) }
					</li>
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __( 'Set expiration date of courses', 'sensei-lms' ) }
					</li>
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __( 'Quiz timer', 'sensei-lms' ) }
					</li>
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __(
							'Flashcards, Image Hotspots, and Checklists',
							'sensei-lms'
						) }
					</li>
					<li className="sensei-editor-wizard-modal-upsell__feature-item">
						{ __( '1 year of updates & support', 'sensei-lms' ) }
					</li>
				</ul>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={
						window.sensei.pluginUrl +
						'assets/dist/images/sensei-pro-upsell.png'
					}
					alt={ __(
						'Illustration of a course listing with the pricing defined and with the button "Purchase Button"',
						'sensei-lms'
					) }
					className="sensei-editor-wizard-modal-upsell__image"
				/>
			</div>
		</div>
	);
};

CourseUpgradeStep.Actions = ( { goToNextStep } ) => {
	const upgrade = () => {
		window.open(
			'https://senseilms.com/pricing/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=course_editor_wizard',
			'sensei-pricing',
			'noreferrer'
		);
		goToNextStep();
	};
	return (
		<div>
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
		</div>
	);
};

export default CourseUpgradeStep;
