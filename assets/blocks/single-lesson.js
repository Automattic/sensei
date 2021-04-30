/**
 * WordPress dependencies
 */
import { subscribe, select, dispatch } from '@wordpress/data';

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
import LessonPropertiesBlock from './lesson-properties';

registerSenseiBlocks( [
	LessonActionsBlock,
	LessonPropertiesBlock,
	CompleteLessonBlock,
	NextLessonBlock,
	ResetLessonBlock,
	ViewQuizBlock,
] );

let needsTemplate;

const unsubscribe = subscribe( () => {
	needsTemplate = select( 'core/editor' ).getEditedPostAttribute( 'meta' )
		?._needs_template; // eslint-disable-line camelcase

	if ( true !== needsTemplate ) {
		return;
	}

	// Add default lesson template to the editor.
	setTimeout( dispatch( 'core/block-editor' ).synchronizeTemplate, 1 );

	dispatch( 'core/editor' ).editPost( {
		meta: { _needs_template: false },
	} );

	unsubscribe();
} );
