import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Edit lesson block component.
 */
const EditModulesBlock = () => (
	<InnerBlocks
		allowedBlocks={ [ 'sensei-lms/course-outline-module' ] }
		templateLock={ false }
		placeholder={ () => 'Hey' }
	/>
);

export default EditModulesBlock;
