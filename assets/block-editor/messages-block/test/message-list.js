/**
 * External dependencies.
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies.
 */
import MessageList from '../message-list';

const messages = [
	{
		id: 100,
		link: 'http://example.com/messages/100',
		displayed_title: 'Re: My Course',
		sender: 'user1',
		displayed_date: '2019-11-16',
		excerpt: {
			rendered: 'Hello, teacher!',
		},
	},
	{
		id: 101,
		link: 'http://example.com/messages/101',
		displayed_title: 'Re: My Lesson',
		sender: 'user2',
		displayed_date: '2019-11-17',
		excerpt: {
			rendered: 'Hello, lesson teacher!',
		},
	},
];

describe( 'MessageList Component', () => {
	it( 'renders the given messages', () => {
		const tree = renderer
			.create( <MessageList messages={ messages } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	it( 'renders the placeholder UI when fetching', () => {
		const tree = renderer
			.create( <MessageList isFetching={ true } messages={ messages } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
