/**
 * WordPress dependencies.
 */
import { registerStore, dispatch } from '@wordpress/data';

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
	// TODO: Using dummy data for now. Should use apiFetch with the REST API instead.
	return new Promise( ( resolve ) => {
		setTimeout( () => {
			resolve(
				[
					{
						id: 1,
						post_title: 'Message thread 1',
						post_content: 'Tell me about the course!',
					},
					{
						id: 2,
						post_title: 'Message thread 2',
						post_content: 'Tell me MORE about the course!',
					},
				]
			)
		}, 1000 );
	} );
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
