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
 * @param {Object} props      Component props.
 * @param {Object} props.data Wizard data.
 */
const LessonPatternsStep = ( { data, ...props } ) => {
	const replaces = {};

	if ( data.lessonTitle ) {
		replaces[ 'sensei-pattern-title' ] = data.lessonTitle;
	}

	return (
		<PatternsStep
			title={ __( 'Lesson Type', 'sensei-lms' ) }
			replaces={ replaces }
			{ ...props }
		/>
	);
};

LessonPatternsStep.Actions = PatternsStep.Actions;

export default LessonPatternsStep;
