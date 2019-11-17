/**
 * WordPress dependencies.
 */
import { useSelect } from '@wordpress/data';

/**
 * External dependencies.
 */
import classnames from 'classnames';

/**
 * Internal dependencies.
 */
import MESSAGES_STORE from '../data/messages-store';
import Message from './message';

function Block() {
	const { messages, isFetching } = useSelect( ( select ) => {
		const store = select( MESSAGES_STORE );

		return {
			messages: store.getMessages(),
			isFetching: store.isFetching(),
		};
	} );
	const classes = classnames( 'message-container', {
		'is-fetching': isFetching,
	} );
	const messagesList = isFetching ? Array.from( { length: 2 } ) : messages;

	return (
		<div className={ classes }>
			{ messagesList.map(
				( message = {}, i ) => (
					<Message key={ message.id || i } message={ message } />
				)
			) }
		</div>
	);
}

export default Block;
