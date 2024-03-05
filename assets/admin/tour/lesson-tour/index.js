/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import SenseiTourKit from '../components/sensei-tour-kit';
import getTourSteps from './steps';

const tourName = 'sensei-lesson-tour';

export default function LessonTour() {
	const { quizBlock } = useSelect( ( select ) => {
		const { getBlocks } = select( 'core/block-editor' );
		const blocks = getBlocks();
		return {
			quizBlock: getFirstBlockByName( 'sensei-lms/quiz', blocks ),
		};
	} );

	if ( ! quizBlock ) {
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
