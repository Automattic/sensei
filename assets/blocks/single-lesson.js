/**
 * WordPress dependencies
 */
import { subscribe, select, dispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import {
	LessonActionsBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
} from './lesson-actions';

registerSenseiBlocks( [
	LessonActionsBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
] );

let needsTemplate;

subscribe( () => {
	if ( undefined !== needsTemplate ) {
		return;
	}

	needsTemplate = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		?._needs_template; // eslint-disable-line camelcase

	if ( true !== needsTemplate ) {
		return;
	}

	// Add default lesson template to the editor.
	setTimeout( () => {
		dispatch( 'core/block-editor' ).resetBlocks(
			select( 'core/block-editor' )
				.getTemplate()
				.map( ( block ) => createBlock( ...block ) )
		);
	}, 1 );

	dispatch( 'core/editor' ).editPost( {
		meta: { _needs_template: false },
	} );
} );
