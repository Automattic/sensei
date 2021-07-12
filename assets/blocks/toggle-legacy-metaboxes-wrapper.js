/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Wrapper to toggle the post metaboxes.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes
 * @param {boolean} props.attributes.isPreview Whether it's a block preview.
 * @param {Object}  props.children             Wrapped children.
 */
const ToggleLegacyMetaboxesWrapper = ( {
	attributes: { isPreview = false },
	children,
} ) => {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);

	useEffect( () => {
		if ( isPreview || ! window.sensei_toggle_legacy_metaboxes ) {
			return;
		}

		window.sensei_toggle_legacy_metaboxes( postType, 'add' );

		return () =>
			window.sensei_toggle_legacy_metaboxes( postType, 'remove' );
	}, [ isPreview ] );

	return children;
};

export default ToggleLegacyMetaboxesWrapper;
