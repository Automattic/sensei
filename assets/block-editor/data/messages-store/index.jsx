/**
 * WordPress dependencies.
 */
import { registerStore, dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

// Store name.
const MESSAGES_STORE = 'sensei-lms/messages';
export default MESSAGES_STORE;

// Default state of the messages store.
const DEFAULT_STATE = {
	messages: [],
	isFetching: false,
	error: null,
};

/**
 * Fetch the messages from the API. Returns a Promise.
 */
function apiFetchMessages() {
	return apiFetch( { path: '/wp/v2/sensei-messages' } );
}

// Actions for messages store.
const actions = {
	fetchMessages() {
		return {
			type: 'FETCH_MESSAGES',
		};
	},

	receiveMessages( messages, error = null ) {
		return {
			type: 'RECEIVE_MESSAGES',
			messages,
			error,
		};
	}
};

// Reducer for messages store.
function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'FETCH_MESSAGES':
			return {
				...state,
				isFetching: true,
			};

		case 'RECEIVE_MESSAGES':
			return {
				...state,
				isFetching: false,
				messages: action.messages,
				error: action.error,
			};
	}

	return state;
}

// Selectors for messages store.
const selectors = {
	getMessages( state ) {
		return state.messages;
	},

	isFetching( state ) {
		return state.isFetching;
	},

	getError( state ) {
		return state.error;
	},
}

// Resolvers for messages store.
const resolvers = {
	getMessages() {
		dispatch( MESSAGES_STORE ).fetchMessages();

		apiFetchMessages().then(
			( messages ) => dispatch( MESSAGES_STORE ).receiveMessages( messages )
		).catch(
			( error ) => dispatch( MESSAGES_STORE ).receiveMessages( [], error )
		);
	},
}

// Register the Sensei Messages store.
registerStore( MESSAGES_STORE, {
	reducer,
	actions,
	selectors,
	resolvers,
} );
