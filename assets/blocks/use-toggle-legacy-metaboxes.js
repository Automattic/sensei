import { useEffect } from '@wordpress/element';

const useToggleLegacyMetaboxes = ( { ignoreToggle = false } ) => {
	useEffect( () => {
		if ( ignoreToggle ) {
			return;
		}
		window.sensei_toggle_legacy_metaboxes();
		return window.sensei_toggle_legacy_metaboxes;
	}, [ ignoreToggle ] );
};

export default useToggleLegacyMetaboxes;
