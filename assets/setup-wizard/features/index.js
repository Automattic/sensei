/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Features step for Setup Wizard.
 */
const Features = () => {
	const { stepData } = useSetupWizardStep( 'features' );

	const percentage = 30;

	return (
		<div className="sensei-setup-wizard__features-step">
			<div
				className="sensei-setup-wizard__features-status"
				role="status"
				aria-live="polite"
			>
				{ __( 'Preparing your tailored experience', 'sensei-lms' ) }
			</div>

			<div className="sensei-setup-wizard__features-progress-bar">
				<div
					role="progressbar"
					aria-label={ __(
						'Sensei Onboarding Progress',
						'sensei-lms'
					) }
					aria-valuenow={ percentage }
					className="sensei-setup-wizard__features-progress-bar-filled"
					style={ { width: `${ percentage }%` } }
				/>
			</div>

			<div>
				To be installed or activated (if not yet):{ ' ' }
				{ stepData.selected.join( ', ' ) }
			</div>
		</div>
	);
};

export default Features;
