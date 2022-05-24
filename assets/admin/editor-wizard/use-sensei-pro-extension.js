/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from '../../extensions/store';

export default function useSenseiProExtension() {
	const { extensions } = useSelect( ( select ) => {
		const store = select( EXTENSIONS_STORE );

		return {
			extensions: store.getExtensions(),
		};
	} );

	return extensions.find(
		( extension ) => extension.product_slug === 'sensei-pro'
	);
}
