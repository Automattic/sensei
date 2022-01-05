/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';

/**
 * Returns a callback which opens the quiz settings.
 *
 * @param {string} clientId The quiz block client id.
 *
 * @return {Function} A callback which opens the quiz settings.
 */
export const useOpenQuizSettings = ( clientId ) => {
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );
	const { selectBlock } = useDispatch( 'core/block-editor' );

	const openQuizSettings = () => {
		selectBlock( clientId );
		openGeneralSidebar( 'edit-post/block' );
	};

	return openQuizSettings;
};
