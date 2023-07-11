/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE } from '../../../extensions/store';
/**
 * External dependencies
 */
import { useMemo } from 'react';

const useSenseiProSettings = () => {
	const extension = select( EXTENSIONS_STORE ).getSenseiProExtension();

	return useMemo(
		() => ( {
			isActivated: Boolean( extension?.is_activated ),
		} ),
		[ extension?.is_activated ]
	);
};
export default useSenseiProSettings;
