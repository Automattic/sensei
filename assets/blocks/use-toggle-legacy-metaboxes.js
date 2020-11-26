import { useEffect } from '@wordpress/element';

const useToggleLegacyMetaboxes = () => {
	useEffect( () => {
		window.sensei_toggleLegacyMetaboxes();
		return window.sensei_toggleLegacyMetaboxes;
	}, [] );
};

export default useToggleLegacyMetaboxes;
