import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 *
 * @typedef {Object} OnboardingApiHandle
 *
 * @property {boolean}          isBusy Loading state.
 * @property {Object}           data   API response from GET call to endpoint.
 * @property {function(Object)} submit Method to POST to endpoint.
 */
/**
 * Use Onboarding REST API for the given step.
 *
 * Loads data via the GET method for the endpoint, and provides a submit function that sends data to the endpoint
 * via POST request.
 *
 * @param {string} step Onboarding step endpoint name.
 * @return {OnboardingApiHandle} handle
 */
export const useOnboardingApi = ( step ) => {
	const [ data, setData ] = useState( {} );
	const [ isBusy, setBusy ] = useState( false );
	const path = `sensei-internal/v1/setup-wizard`;

	const fetchData = useCallback( async () => {
		setBusy( true );
		const result = await apiFetch( {
			path,
		} );
		setData( result[step] );
		setBusy( false );
	}, [ path, step ] );

	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	async function submit( formData ) {
		setBusy( true );
		await apiFetch( {
			path: `${ path }/${ step }`,
			method: 'POST',
			data: formData,
		} );
		setBusy( false );
	}

	return { data, submit, isBusy };
};
