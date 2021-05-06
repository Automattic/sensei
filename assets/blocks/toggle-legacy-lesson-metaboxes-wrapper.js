/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Wrapper to toggle the lesson metaboxes.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes
 * @param {boolean} props.attributes.isPreview Whether it's a block preview.
 * @param {Object}  props.children             Wrapped children.
 */
const ToggleLegacyLessonMetaboxesWrapper = ( {
	attributes: { isPreview = false },
	children,
} ) => {
	useEffect( () => {
		if ( isPreview || ! window.sensei_toggle_legacy_lesson_metaboxes ) {
			return;
		}

		window.sensei_toggle_legacy_lesson_metaboxes();

		return window.sensei_toggle_legacy_lesson_metaboxes;
	}, [ isPreview ] );

	return children;
};

export default ToggleLegacyLessonMetaboxesWrapper;
