/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Features step for Setup Wizard.
 */
const Features = () => {
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
		</div>
	);
};

export default Features;
