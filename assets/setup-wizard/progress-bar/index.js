/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';

/**
 * Progress Bar component.
 *
 * @param {Object} props       Component props.
 * @param {Array}  props.steps The available steps.
 */
const ProgressBar = ( { steps } ) => {
	const { currentRoute } = useQueryStringRouter();

	const length = steps.length;
	const currentStep = steps.findIndex(
		( step ) => step.key === currentRoute
	);

	// It considers the current step as filled.
	const percentage = ( ( currentStep + 1 ) / length ) * 100;

	return (
		<div className="sensei-setup-wizard__progress-bar">
			<div
				role="progressbar"
				aria-label={ __( 'Sensei Onboarding Progress', 'sensei-lms' ) }
				aria-valuenow={ percentage }
				className="sensei-setup-wizard__progress-bar-filled"
				style={ { width: `${ percentage }%` } }
			/>
		</div>
	);
};

export default ProgressBar;
