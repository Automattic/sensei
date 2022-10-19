/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { buildJobEndpointUrl } from '../data-port/import/helpers/url';
import Link from './link';

const useDemoCourseInstaller = () => {
	const [ hasError, setHasError ] = useState( false );
	const [ jobId, setJobId ] = useState( null );
	const [ pollingCount, setPollingCount ] = useState( 0 );

	const catchError = () => {
		setPollingCount( 0 );
		setHasError( true );
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
					setPollingCount( -1 );
					return;
				}

				setPollingCount( ( n ) => ( n + 1 ) % 3 );
			} )
			.catch( catchError );
	}, [ jobId, pollingCount ] );

	// Start installation job.
	useEffect( () => {
		apiFetch( {
			path: buildJobEndpointUrl( null, [ 'start-sample' ] ),
			method: 'POST',
		} )
			.then( ( res ) => {
				setJobId( res.id );
			} )
			.catch( catchError );
	}, [] );

	return [ jobId, pollingCount, hasError ];
};

/**
 * Component to Install Demo Course. Invoked when clicking on a link pointing to "sensei://install-demo-course".
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.remove      Function to call to remove the item from the Quick Links Column.
 * @param {Function} props.restoreLink Function to call to restore the link on the Quick Links Column.
 */
function InstallDemoCourse( { remove, restoreLink } ) {
	const [ jobId, pollingCount, error ] = useDemoCourseInstaller();
	const [ showInstalledLink, setShowInstalledLink ] = useState( false );
	useEffect( () => {
		let run = null;
		if ( error ) {
			run = restoreLink;
		} else if ( ! error && pollingCount < 0 ) {
			run = () => setShowInstalledLink( true );
		}
		if ( run ) {
			setTimeout( run, 2000 );
		}
	}, [ error, pollingCount, remove, restoreLink ] );
	if ( error ) {
		return __( 'Error while installing. Try again.', 'sensei-lms' );
	}
	if ( showInstalledLink ) {
		const { setupSampleCourseNonce } = window.sensei_home;
		return (
			<Link
				url={ `?redirect_imported_sample=1&job_id=${ jobId }&nonce=${ setupSampleCourseNonce }` }
				label={ __( 'Edit Demo Course', 'sensei-lms' ) }
			/>
		);
	}
	if ( pollingCount < 0 ) {
		return __( 'Installed', 'sensei-lms' );
	}
	return (
		<>
			<Spinner />
			{ __( 'Installing', 'sensei-lms' ) }
		</>
	);
}

export default InstallDemoCourse;
