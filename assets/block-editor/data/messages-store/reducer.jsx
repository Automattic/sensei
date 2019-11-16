// Default state of the messages store.
const DEFAULT_STATE = {
	messages: [],
	isFetching: false,
	error: null,
};

export default function reducer( state = DEFAULT_STATE, action ) {
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
