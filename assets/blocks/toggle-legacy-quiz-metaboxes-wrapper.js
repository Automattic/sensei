/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Wrapper to toggle the quiz metaboxes.
 *
 * @param {Object} props
 * @param {Object} props.children Wrapped children.
 */
const ToggleLegacyQuizMetaboxesWrapper = ( { children } ) => {
	useEffect( () => {
		if ( ! window.sensei_toggle_legacy_quiz_metaboxes ) {
			return;
		}

		window.sensei_toggle_legacy_quiz_metaboxes();

		return window.sensei_toggle_legacy_quiz_metaboxes;
	}, [] );

	return children;
};

export default ToggleLegacyQuizMetaboxesWrapper;
