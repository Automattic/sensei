import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Features polling hook.
 *
 * @param {boolean} active Flag whether polling is active.
 */
const useFeaturesPolling = ( active ) => {
	const [ pollingCount, setPollingCount ] = useState( 0 );

	const features = useSelect(
		( select ) =>
			select( 'sensei/setup-wizard' ).getStepData( 'features', true ),
		[ pollingCount ]
	);
	const { invalidateResolution } = useDispatch( 'sensei/setup-wizard' );

	useEffect( () => {
		if ( ! active ) {
			return;
		}

		// Invalidate resolution to get fresh content from the server.
		invalidateResolution( 'getStepData', [ 'features', true ] );

		const timer = setTimeout( () => {
			setPollingCount( ( n ) => n + 1 );
		}, 5000 );

		return () => {
			clearTimeout( timer );
		};
	}, [ pollingCount, active, invalidateResolution ] );

	return features;
};

export default useFeaturesPolling;
