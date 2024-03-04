/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import SenseiTourKit from '../components/sensei-tour-kit';
import getTourSteps from './steps';

export const getQuizBlock = () =>
	getFirstBlockByName(
		'sensei-lms/quiz',
		select( 'core/block-editor' ).getBlocks()
	);

const tourName = 'sensei-lesson-tour';

export default function LessonTour() {
	if ( ! getQuizBlock() ) {
		return null;
	}

	return (
		<SenseiTourKit
			trackId="lesson_quiz_onboarding_step_complete"
			tourName={ tourName }
			steps={ getTourSteps() }
		/>
	);
}

registerPlugin( tourName, {
	render: () => <LessonTour />,
} );
