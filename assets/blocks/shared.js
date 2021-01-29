/**
 * WordPress dependencies
 */
import { unregisterBlockType } from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import ContactTeacherBlock from './contact-teacher-block';

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
			if ( ! blockPostTypes.includes( postType ) ) {
				unregisterBlockType( blockName );
			}
		}
	);

	unsubscribe();
} );
