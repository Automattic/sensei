/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Preparing step for Setup Wizard.
 */
const Preparing = () => {
	const percentage = 30;

	return (
		<div className="sensei-setup-wizard__preparing-step">
			<div
				className="sensei-setup-wizard__preparing-status"
				role="status"
				aria-live="polite"
			>
				{ __( 'Preparing your tailored experience', 'sensei-lms' ) }
			</div>

			<div className="sensei-setup-wizard__preparing-progress-bar">
				<div
					role="progressbar"
					aria-label={ __(
						'Sensei Onboarding Progress',
						'sensei-lms'
					) }
					aria-valuenow={ percentage }
					className="sensei-setup-wizard__preparing-progress-bar-filled"
					style={ { width: `${ percentage }%` } }
				/>
			</div>
		</div>
	);
};

export default Preparing;
