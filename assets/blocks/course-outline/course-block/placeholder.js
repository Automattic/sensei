import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import settings from './index';

/**
 * Placeholder for empty Course Outline block.
 *
 * @param {Function} addBlock Add block
 */
export const CourseOutlinePlaceholder = ( { addBlock } ) => (
	<Placeholder
		label={ __( 'Course Outline', 'sensei-lms' ) }
		icon={ settings.icon() }
		instructions={ __(
			'Build and display a course outline. A course is made up of modules (optional) and lessons. You can use modules to group related lessons together.',
			'sensei-lms'
		) }
	>
		<Button isSecondary onClick={ () => addBlock( 'module' ) }>
			{ __( 'Create a module', 'sensei-lms' ) }
		</Button>
		<Button isSecondary onClick={ () => addBlock( 'lesson' ) }>
			{ __( 'Create a lesson', 'sensei-lms' ) }
		</Button>
	</Placeholder>
);
