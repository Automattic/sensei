import { useEffect } from '@wordpress/element';

const useToggleLegacyMetaboxes = () => {
	useEffect( () => {
		window.sensei_toggle_legacy_metaboxes();
		return window.sensei_toggle_legacy_metaboxes;
	}, [] );
};

export default useToggleLegacyMetaboxes;
