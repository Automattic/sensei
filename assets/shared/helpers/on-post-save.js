/**
 * WordPress dependencies
 */
import { subscribe, select } from '@wordpress/data';

/**
 * A callback for Gutenberg post save.
 *
 * @param {Function} callback Callback function.
 *
 * @return {Function} Unsubscribe function.
 */
const onPostSave = ( callback ) => {
	const coreEditorSelector = select( 'core/editor' );
	let wasSaving = false;

	const unsubscribe = subscribe( () => {
		const isSaving =
			coreEditorSelector.isSavingPost() &&
			! coreEditorSelector.isAutosavingPost();

		// Check if it completed a saving.
		if ( wasSaving && ! isSaving ) {
			wasSaving = isSaving;
			callback();
		} else {
			wasSaving = isSaving;
		}
	} );

	return unsubscribe;
};

export default onPostSave;
