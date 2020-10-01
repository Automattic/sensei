import { BlockIcon } from '@wordpress/block-editor';
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
		icon={ <BlockIcon icon={ settings.icon } showColors /> }
		instructions={ __(
			'Build and display a course outline. A course is made up of modules (optional) and lessons. You can use modules to group related lessons together.',
			'sensei-lms'
		) }
	>
		<Button isDefault onClick={ () => addBlock( 'module' ) }>
			{ __( 'Create a module', 'sensei-lms' ) }
		</Button>{ ' ' }
		<Button isDefault onClick={ () => addBlock( 'lesson' ) }>
			{ __( 'Create a lesson', 'sensei-lms' ) }
		</Button>
	</Placeholder>
);
