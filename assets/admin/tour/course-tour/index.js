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

export const getOutlineBlock = () =>
	getFirstBlockByName(
		'sensei-lms/course-outline',
		select( 'core/block-editor' ).getBlocks()
	);

const tourName = 'sensei-course-tour';

export default function CourseTour() {
	if ( ! getOutlineBlock() ) {
		return null;
	}

	return (
		<SenseiTourKit
			trackId="course_outline_onboarding_step_complete"
			tourName={ tourName }
			steps={ getTourSteps() }
		/>
	);
}

registerPlugin( tourName, {
	render: () => <CourseTour />,
} );
