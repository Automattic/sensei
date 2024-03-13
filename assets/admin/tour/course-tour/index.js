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
import { useState } from '@wordpress/element';

const tourName = 'sensei-course-tour';

export default function CourseTour() {
	const { courseOutlineBlock } = useSelect( ( select ) => {
		const { getBlocks } = select( 'core/block-editor' );
		const blocks = getBlocks();
		return {
			courseOutlineBlock: getFirstBlockByName(
				'sensei-lms/course-outline',
				blocks
			),
		};
	} );
	const [ tourSteps ] = useState( getTourSteps() );

	if ( ! courseOutlineBlock ) {
		return null;
	}

	return (
		<SenseiTourKit
			trackId="course_outline_onboarding_step_complete"
			tourName={ tourName }
			steps={ tourSteps }
		/>
	);
}

registerPlugin( tourName, {
	render: () => <CourseTour />,
} );
