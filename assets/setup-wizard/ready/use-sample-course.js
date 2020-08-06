import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { buildJobEndpointUrl } from '../../data-port/import/helpers/url';
import { logEvent } from '../log-event';

const useSampleCourse = () => {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ isCompleted, setIsCompleted ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ jobId, setJobId ] = useState( null );
	const [ pollingCount, setPollingCount ] = useState( 0 );

	const pollingInterval = 2000;

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

		const timer = setTimeout( () => {
			apiFetch( {
				path: buildJobEndpointUrl( jobId ),
			} )
				.then( ( res ) => {
					if ( 'completed' === res.status.status ) {
						setIsInstalling( false );
						setIsCompleted( true );
						setJobId( null );

						return;
					}

					setPollingCount( ( n ) => n + 1 );
				} )
				.catch( catchError );
		}, pollingInterval );

		return () => {
			clearTimeout( timer );
		};
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

	return [ start, isInstalling, isCompleted, error ];
};

export default useSampleCourse;
