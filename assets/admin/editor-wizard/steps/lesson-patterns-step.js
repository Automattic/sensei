/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternsStep from './patterns-step';

/**
 * Lesson patterns step.
 *
 * @param {Object} props Component props.
 */
const LessonPatternsStep = ( props ) => (
	<PatternsStep title={ __( 'Lesson Type', 'sensei-lms' ) } { ...props } />
);

LessonPatternsStep.Actions = PatternsStep.Actions;

export default LessonPatternsStep;
