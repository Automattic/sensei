import { render, fireEvent, waitFor } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

import EditLessonBlock from './edit';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	withColors() {
		return ( Component ) => ( props ) => (
			<Component { ...props } backgroundColor={ {} } textColor={ {} } />
		);
	},
} ) );

jest.mock( './settings', () => ( {
	LessonBlockSettings: () => '',
} ) );

jest.mock( '../status-control', () => ( {
	Statuses: {
		IN_PROGRESS: 'In Progress',
	},
} ) );

describe( '<EditLessonBlock />', () => {
	const selectNextBlockMock = jest.fn();
	const removeBlockMock = jest.fn();
	const createBlockMock = jest.fn();

	useDispatch.mockImplementation( () => ( {
		selectNextBlock: selectNextBlockMock,
		removeBlock: removeBlockMock,
	} ) );

	createBlock.mockImplementation( createBlockMock );

	afterEach( () => {
		selectNextBlockMock.mockReset();
		removeBlockMock.mockReset();
		createBlockMock.mockReset();
	} );

	it( 'Should render the edit lesson block correctly', () => {
		const { container, getByPlaceholderText } = render(
			<EditLessonBlock
				className="custom-class"
				attributes={ { title: 'Test' } }
			/>
		);

		expect( getByPlaceholderText( 'Lesson name' ) ).toBeTruthy();
		expect( container.querySelector( '.custom-class' ) ).toBeTruthy();
	} );

	it( 'Should set the title attribute on changing the input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<EditLessonBlock
				attributes={ { title: '' } }
				setAttributes={ setAttributesMock }
			/>
		);

		fireEvent.change( getByPlaceholderText( 'Lesson name' ), {
			target: { value: 'Test' },
		} );
		expect( setAttributesMock ).toBeCalledWith( { title: 'Test' } );
	} );

	it( 'Should create new block when pressing enter with title filled', async () => {
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<EditLessonBlock
				name="block-name"
				attributes={ { title: 'Test' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Lesson name' ), {
			keyCode: 13,
		} );

		await waitFor( () =>
			expect( createBlockMock ).toBeCalledWith( 'block-name' )
		);
		await waitFor( () => expect( insertBlocksAfterMock ).toBeCalled() );
	} );

	it( 'Should not create new block when pressing enter with title empty', async () => {
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<EditLessonBlock
				attributes={ { title: '' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Lesson name' ), {
			keyCode: 13,
		} );

		await waitFor( () => expect( createBlockMock ).not.toBeCalled() );
		await waitFor( () => expect( insertBlocksAfterMock ).not.toBeCalled() );
	} );

	it( 'Should focus on the next block when pressing enter and there is a next block', async () => {
		selectNextBlockMock.mockReturnValue( [ '123' ] );
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<EditLessonBlock
				attributes={ { title: 'Test' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Lesson name' ), {
			keyCode: 13,
		} );

		await waitFor( () => expect( selectNextBlockMock ).toBeCalled() );
		await waitFor( () => expect( createBlockMock ).not.toBeCalled() );
		await waitFor( () => expect( insertBlocksAfterMock ).not.toBeCalled() );
	} );

	it( 'Should remove the block when pressing backspace in an empty input', () => {
		const { getByPlaceholderText } = render(
			<EditLessonBlock attributes={ { title: '' } } />
		);

		fireEvent.keyDown( getByPlaceholderText( 'Lesson name' ), {
			keyCode: 8,
		} );

		expect( removeBlockMock ).toBeCalled();
	} );

	it( 'Should not remove the block when pressing backspace in a filled input', () => {
		const { getByPlaceholderText } = render(
			<EditLessonBlock attributes={ { title: 'Test' } } />
		);

		fireEvent.keyDown( getByPlaceholderText( 'Lesson name' ), {
			keyCode: 8,
		} );

		expect( removeBlockMock ).not.toBeCalled();
	} );
} );
