/**
 * WordPress dependencies
 */
import { BlockIcon } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { addAction, doAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import settings from './index';

const createCourseOutline = () => {
	// eslint-disable-next-line no-console
	console.log( 'Message from Sensei LMS' );
};

/**
 * Placeholder for empty Course Outline block.
 *
 */
const OutlinePlaceholder = () => (
	<Placeholder
		className="wp-block-sensei-lms-course-outline__placeholder"
		label={ __( 'Course Outline', 'sensei-lms' ) }
		icon={ <BlockIcon icon={ settings.icon } showColors /> }
		instructions={ __(
			'Build and display a course outline. A course is made up of modules (optional) and lessons. You can use modules to group related lessons together.',
			'sensei-lms'
		) }
	>
		<Button
			isDefault
			onClick={ () => doAction( 'sensei.courseOutline.blank' ) }
			className="is-large"
		>
			{ __( 'Start with blank', 'sensei-lms' ) }
		</Button>
		<Button
			isDefault
			onClick={ () => doAction( 'sensei.courseOutline.ai' ) }
			className="is-large"
		>
			{ __( 'Generate with AI', 'sensei-lms' ) }
		</Button>
	</Placeholder>
);

export default OutlinePlaceholder;

addAction(
	'sensei.courseOutline.blank',
	'sensei-lms/course-outline',
	createCourseOutline
);
