/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Progress polling hook.
 */
const useProgressPolling = () => {
	const { pollJobProgress } = useDispatch( 'sensei/import' );

	const jobId = useSelect( ( select ) =>
		select( 'sensei/import' ).getJobId()
	);
	useEffect( () => {
		pollJobProgress( jobId );
	}, [ pollJobProgress, jobId ] );
};

export default useProgressPolling;
