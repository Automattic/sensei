import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Progress polling hook.
 *
 * @param {boolean} isActive Flag whether polling is active.
 * @param {string}  jobId    ID for the current job.
 */
const useProgressPolling = ( isActive, jobId ) => {
	const [ pollingCount, setPollingCount ] = useState( 0 );
	const { updateJobState } = useDispatch( 'sensei/import' );

	const stepState = useSelect(
		( select ) => select( 'sensei/import' ).getStepData( 'progress' ),
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
