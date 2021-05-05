/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { buildJobEndpointUrl } from '../../data-port/import/helpers/url';
import { logEvent } from '../../shared/helpers/log-event';

const useSampleCourseInstaller = () => {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ jobId, setJobId ] = useState( null );
	const [ pollingCount, setPollingCount ] = useState( 0 );

	const catchError = ( e ) => {
		setIsInstalling( false );
		setError( e.message );
		setJobId( null );
	};

	// Logs polling.
	useEffect( () => {
		if ( ! jobId ) {
			return;
		}

		apiFetch( {
			path: buildJobEndpointUrl( jobId, [ 'process' ] ),
			method: 'POST',
		} )
			.then( ( res ) => {
				if ( 'completed' === res.status.status ) {
					const { nonce } = window.sensei_setup_wizard;
					window.location.assign(
						`?redirect_imported_sample=1&job_id=${ jobId }&nonce=${ nonce }`
					);

					return;
				}

				setPollingCount( ( n ) => n + 1 );
			} )
			.catch( catchError );
	}, [ jobId, pollingCount ] );

	// Start installation job.
	const start = () => {
		setIsInstalling( true );
		setError( null );

		apiFetch( {
			path: buildJobEndpointUrl( null, [ 'start-sample' ] ),
			method: 'POST',
		} )
			.then( ( res ) => {
				setJobId( res.id );
			} )
			.catch( catchError );

		logEvent( 'setup_wizard_ready_install_course' );
	};

	return [ start, isInstalling, error ];
};

export default useSampleCourseInstaller;
