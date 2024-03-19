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
import getTourSteps, { beforeEach } from './steps';
import { useState } from '@wordpress/element';

const tourName = 'sensei-lesson-tour';

export default function LessonTour() {
	const { quizBlock } = useSelect( ( select ) => {
		const { getBlocks } = select( 'core/block-editor' );
		const blocks = getBlocks();
		return {
			quizBlock: getFirstBlockByName( 'sensei-lms/quiz', blocks ),
		};
	} );
	const [ tourSteps ] = useState( getTourSteps() );

	if ( ! quizBlock ) {
		return null;
	}

	return (
		<SenseiTourKit
			trackId="lesson_quiz_onboarding_step_complete"
			tourName={ tourName }
			steps={ tourSteps }
			beforeEach={ beforeEach }
		/>
	);
}

registerPlugin( tourName, {
	render: () => <LessonTour />,
} );
