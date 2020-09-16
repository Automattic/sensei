import { dispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

let listeners = [];
let savePost;

/**
 * Wrap core/editor savePost action to run registered callbacks before saving.
 */
function setupSavePostHook() {
	if ( savePost ) return;
	const editor = dispatch( 'core/editor' );
	savePost = editor.savePost;

	editor.savePost = () => {
		listeners.forEach( ( callback ) => callback() );
		savePost();
	};
}

/**
 * Add a callback to be run before saving the post.
 *
 * @param {Function} callback Function to call when saving.
 */
export function useSavePost( callback ) {
	useEffect( () => {
		setupSavePostHook();

		const callbackwp = () => {
			callback();
		};
		listeners.push( callbackwp );

		return () => {
			listeners = listeners.filter( ( item ) => item !== callbackwp );
		};
	}, [ callback ] );
}
