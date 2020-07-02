import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Progress polling hook.
 *
 * @param {boolean} isActive Flag whether polling is active.
 */
const useProgressPolling = ( isActive ) => {
	const [ pollingCount, setPollingCount ] = useState( 0 );
	const { updateJobState } = useDispatch( 'sensei/import' );

	const { jobId, stepState } = useSelect(
		( select ) => {
			const store = select( 'sensei/import' );

			return {
				jobId: store.getJobId(),
				stepState: store.getStepData( 'progress' ),
			};
		},
		[ pollingCount ]
	);

	useEffect( () => {
		if ( ! isActive ) {
			return;
		}
		const timer = setTimeout( () => {
			updateJobState( jobId );
			setPollingCount( ( n ) => n + 1 );
		}, 5000 );

		return () => {
			clearTimeout( timer );
		};
	}, [ pollingCount, isActive, jobId, updateJobState ] );

	return stepState;
};

export default useProgressPolling;
