/**
 * External dependencies.
 */
import ShallowRenderer from 'react-test-renderer/shallow';

/**
 * Internal dependencies.
 */
import MessageList from '../message-list';
import Message from '../message';

const message1 = { id: 100 };
const message2 = { id: 101 };
const message3 = { id: 102 };
const messages = [ message1, message2, message3 ];

const shallowRender = ( component ) => {
	const renderer = new ShallowRenderer();
	renderer.render( component );
	return renderer.getRenderOutput();
}

describe( 'MessageList Component', () => {
	it( 'renders the given messages', () => {
		const result = shallowRender( <MessageList messages={ messages } /> );

		expect( result.props.className ).toEqual( 'message-container' );
		expect( result.props.children ).toEqual( [
			<Message key={ message1.id } message={ message1 } />,
			<Message key={ message2.id } message={ message2 } />,
			<Message key={ message3.id } message={ message3 } />
		] );
	} );

	describe( 'when fetching', () => {
		const result = shallowRender( <MessageList isFetching={ true } messages={ messages } /> );

		it( 'adds the "is-fetching" class', () => {
			expect( result.props.className ).toEqual( 'message-container is-fetching' );
		} );

		it( 'renders placeholder messages', () => {
			expect( result.props.children ).toEqual( [
				<Message key={ 0 } message={ {} } />,
				<Message key={ 1 } message={ {} } />
			] );
		} );
	} );
} );
