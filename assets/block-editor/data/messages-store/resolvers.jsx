/**
 * WordPress dependencies.
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies.
 */
import { MESSAGES_STORE } from './name';

/**
 * Fetch the messages from the API. Returns a Promise.
 */
function apiFetchMessages() {
	return apiFetch( { path: '/wp/v2/sensei-messages' } );
}

export function getMessages() {
	dispatch( MESSAGES_STORE ).fetchMessages();

	apiFetchMessages().then(
		( messages ) => dispatch( MESSAGES_STORE ).receiveMessages( messages )
	).catch(
		( error ) => dispatch( MESSAGES_STORE ).receiveMessages( [], error )
	);
}
