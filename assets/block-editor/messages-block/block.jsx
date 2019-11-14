/**
 * WordPress dependencies.
 */
import { useSelect } from '@wordpress/data';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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

	if ( isFetching ) {
		return <Placeholder label={ __( 'Loading Messages...' ) } />;
	}

	return (
		<div className='message-container'>
			{ messages.map(
				( message ) => <Message key={message.id} message={message} />
			) }
		</div>
	);
}

export default Block;
