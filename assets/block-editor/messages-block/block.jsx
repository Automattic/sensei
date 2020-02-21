/**
 * WordPress dependencies.
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import MESSAGES_STORE from '../data/messages-store';
import MessageList from './message-list';

function Block() {
	const { messages, isFetching } = useSelect( ( select ) => {
		const store = select( MESSAGES_STORE );

		return {
			messages: store.getMessages(),
			isFetching: store.isFetching(),
		};
	} );

	return <MessageList
		messages={ messages }
		isFetching={ isFetching }
	/>;
}

export default Block;
