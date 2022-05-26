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
 * @param {Object} props Component props.
 */
const CoursePatternsStep = ( props ) => (
	<PatternsStep title={ __( 'Course Layout', 'sensei-lms' ) } { ...props } />
);

CoursePatternsStep.Actions = PatternsStep.Actions;

export default CoursePatternsStep;
