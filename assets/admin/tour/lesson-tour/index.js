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

export default function LessonTour() {
	if ( ! getQuizBlock() ) {
		return null;
	}

	return <SenseiTourKit steps={ getTourSteps() } />;
}

registerPlugin( 'sensei-lesson-tour', {
	render: () => <LessonTour />,
} );
