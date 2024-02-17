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

export default function CourseTour() {
	if ( ! getOutlineBlock() ) {
		return null;
	}

	return <SenseiTourKit steps={ getTourSteps() } />;
}

registerPlugin( 'sensei-course-tour', {
	render: () => <CourseTour />,
} );
