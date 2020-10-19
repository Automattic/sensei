import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Edit lesson block component.
 */
const EditLessonsBlock = () => (
	<InnerBlocks
		allowedBlocks={ [ 'sensei-lms/course-outline-lesson' ] }
		templateLock={ false }
		placeholder={ () => 'Hey' }
	/>
);

export default EditLessonsBlock;
