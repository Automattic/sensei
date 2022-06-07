/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';

/**
 * Course patterns step.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.data Wizard data.
 */
const CoursePatternsStep = ( { data, ...props } ) => {
	const replaces = {};

	if ( data.courseTitle ) {
		replaces[ 'sensei-content-title' ] = data.courseTitle;
	}

	if ( data.courseDescription ) {
		replaces[ 'sensei-content-description' ] = data.courseDescription;
	}

	return (
		<PatternsStep
			title={ __( 'Course Layout', 'sensei-lms' ) }
			replaces={ replaces }
			{ ...props }
		/>
	);
};

CoursePatternsStep.Actions = PatternsStep.Actions;

export default CoursePatternsStep;
