/**
 * External dependencies.
 */
import classnames from 'classnames';

/**
 * Internal dependencies.
 */
import Message from './message';

function MessageList( { messages, isFetching } ) {
	const classes = classnames( 'message-container', {
		'is-fetching': isFetching,
	} );

	// Render placeholders when fetching.
	messages = isFetching ? Array.from( { length: 2 } ) : messages;

	return (
		<div className={ classes }>
			{ messages.map(
				( message = {}, i ) => (
					<Message key={ message.id || i } message={ message } />
				)
			) }
		</div>
	);
}

export default MessageList;
