export function fetchMessages() {
	return {
		type: 'FETCH_MESSAGES',
	};
}

export function receiveMessages( messages, error = null ) {
	return {
		type: 'RECEIVE_MESSAGES',
		messages,
		error,
	};
}
