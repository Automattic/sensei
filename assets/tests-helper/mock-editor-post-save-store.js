/**
 * WordPress dependencies
 */
import { controls } from '@wordpress/data-controls';

/**
 * Mock 'core/editor' store for testing integration with post saving.
 */
const mockEditorStore = {
	reducer: ( state = { isSavingPost: false }, { type, isSavingPost } ) => {
		switch ( type ) {
			case 'SET_SAVING_POST':
				return { isSavingPost };
			default:
				return { ...state };
		}
	},
	actions: {
		*savePost() {
			yield { type: 'SET_SAVING_POST', isSavingPost: true };
			yield { type: 'RANDOM_STORE_STUFF' };
			yield { type: 'RANDOM_STORE_STUFF' };
			yield { type: 'SET_SAVING_POST', isSavingPost: false };
		},
	},
	selectors: {
		isSavingPost: ( { isSavingPost } ) => isSavingPost,
		isAutosavingPost: () => false,
	},
	controls,
};

export default mockEditorStore;
