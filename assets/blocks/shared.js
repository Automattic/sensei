/**
 * WordPress dependencies
 */
import { unregisterBlockType, getBlockTypes } from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import ContactTeacherBlock from './contact-teacher-block';

// Post types where blocks should be loaded. Or null if it should be loaded for any post type.
const BLOCKS_PER_POST_TYPE = {
	'sensei-lms/button-contact-teacher': [ 'course', 'lesson' ],
};

registerSenseiBlocks( [ ContactTeacherBlock ] );

let postType = null;

const unsubscribe = subscribe( () => {
	postType = select( 'core/editor' ).getCurrentPostType();

	if ( ! postType ) {
		return;
	}

	// Unregister blocks that should not appear in certain post types.
	Object.entries( BLOCKS_PER_POST_TYPE ).forEach(
		( [ blockName, blockPostTypes ] ) => {
			if (
				null !== blockPostTypes &&
				! blockPostTypes.includes( postType ) &&
				getBlockTypes().find( ( b ) => b.name === blockName )
			) {
				unregisterBlockType( blockName );
			}
		}
	);

	unsubscribe();
} );
