/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Wrapper to toggle the course metaboxes.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes
 * @param {boolean} props.attributes.isPreview Whether it's a preview.
 * @param {Object}  props.children             Wrapped children.
 */
const ToggleLegacyCourseMetaboxesWrapper = ( {
	attributes: { isPreview = false },
	children,
} ) => {
	useEffect( () => {
		if ( isPreview || ! window.sensei_toggle_legacy_metaboxes ) {
			return;
		}

		window.sensei_toggle_legacy_metaboxes();

		return window.sensei_toggle_legacy_metaboxes;
	}, [ isPreview ] );

	return children;
};

export default ToggleLegacyCourseMetaboxesWrapper;
