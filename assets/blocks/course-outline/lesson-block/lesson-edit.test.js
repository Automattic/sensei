/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useDispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { LessonEdit } from './lesson-edit';

jest.mock( '@wordpress/data' );

jest.mock( '@wordpress/blocks' );
jest.mock( '../../../shared/blocks/settings', () => ( {
	withColorSettings: () => ( Component ) => Component,
} ) );

jest.mock( './lesson-settings', () => () => '' );
jest.mock( '../../../shared/blocks/single-line-input', () => ( props ) => (
	<input
		{ ...props }
		onChange={ ( event ) => props.onChange( event.currentTarget.value ) }
	/>
) );

jest.mock( '../status-preview', () => ( {
	Status: {
		IN_PROGRESS: 'in-progress',
	},
} ) );

describe( '<LessonEdit />', () => {
	const selectNextBlockMock = jest.fn();
	const removeBlockMock = jest.fn();
	const createBlockMock = jest.fn();

	useDispatch.mockImplementation( () => ( {
		selectNextBlock: selectNextBlockMock,
		removeBlock: removeBlockMock,
		ignoreLesson: jest.fn(),
		trackLesson: jest.fn(),
	} ) );

	beforeEach( () => {
		select.mockReturnValue( {
			getBlock: () => null,
			getNextBlockClientId: () => null,
		} );
	} );

	createBlock.mockImplementation( createBlockMock );

	afterEach( () => {
		selectNextBlockMock.mockReset();
		removeBlockMock.mockReset();
		createBlockMock.mockReset();
	} );

	it( 'Should render the edit lesson block correctly', () => {
		const { container, getByPlaceholderText } = render(
			<LessonEdit
				className="custom-class"
				attributes={ { title: 'Test' } }
			/>
		);

		expect( getByPlaceholderText( 'Add Lesson' ) ).toBeTruthy();
		expect( container.querySelector( '.custom-class' ) ).toBeTruthy();
	} );

	it( 'Should render the edit lesson block with preview correctly', () => {
		const { getByText } = render(
			<LessonEdit
				className="custom-class"
				attributes={ { title: 'Test', preview: true } }
			/>
		);

		expect( getByText( 'Preview' ) ).toBeTruthy();
	} );

	it( 'Should set the title attribute on changing the input value', () => {
		const setAttributesMock = jest.fn();
		const { getByPlaceholderText } = render(
			<LessonEdit
				attributes={ { title: '' } }
				setAttributes={ setAttributesMock }
			/>
		);

		fireEvent.change( getByPlaceholderText( 'Add Lesson' ), {
			target: { value: 'Test' },
		} );
		expect( setAttributesMock ).toBeCalledWith( { title: 'Test' } );
	} );

	it( 'Should create new block when pressing enter', async () => {
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<LessonEdit
				name="block-name"
				attributes={ { title: 'Test' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Add Lesson' ), {
			keyCode: 13,
		} );

		await waitFor( () =>
			expect( createBlockMock ).toBeCalledWith( 'block-name' )
		);
		await waitFor( () => expect( insertBlocksAfterMock ).toBeCalled() );
	} );

	it( 'Should not create new block when there is already one after it', async () => {
		select.mockReturnValue( {
			getBlock: () => ( { clientId: '1', attributes: { title: '' } } ),
			getNextBlockClientId: () => null,
		} );
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<LessonEdit
				attributes={ { title: '' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Add Lesson' ), {
			keyCode: 13,
		} );

		await waitFor( () => expect( createBlockMock ).not.toBeCalled() );
		await waitFor( () => expect( insertBlocksAfterMock ).not.toBeCalled() );
	} );

	it( 'Should focus on the next block when pressing enter and there is a next empty block', async () => {
		select.mockReturnValue( {
			getBlock: () => ( {
				clientId: '1',
				attributes: { title: '' },
			} ),
			getNextBlockClientId: () => null,
		} );
		const insertBlocksAfterMock = jest.fn();

		const { getByPlaceholderText } = render(
			<LessonEdit
				attributes={ { title: 'Test' } }
				insertBlocksAfter={ insertBlocksAfterMock }
			/>
		);

		fireEvent.keyDown( getByPlaceholderText( 'Add Lesson' ), {
			keyCode: 13,
		} );

		await waitFor( () => expect( selectNextBlockMock ).toBeCalled() );
		await waitFor( () => expect( createBlockMock ).not.toBeCalled() );
		await waitFor( () => expect( insertBlocksAfterMock ).not.toBeCalled() );
	} );

	it( 'Should remove the block when pressing backspace in an empty input', () => {
		const { getByPlaceholderText } = render(
			<LessonEdit attributes={ { title: '' } } />
		);

		fireEvent.keyDown( getByPlaceholderText( 'Add Lesson' ), {
			keyCode: 8,
		} );

		expect( removeBlockMock ).toBeCalled();
	} );

	it( 'Should not remove the block when pressing backspace in a filled input', () => {
		const { getByPlaceholderText } = render(
			<LessonEdit attributes={ { title: 'Test' } } />
		);

		fireEvent.keyDown( getByPlaceholderText( 'Add Lesson' ), {
			keyCode: 8,
		} );

		expect( removeBlockMock ).not.toBeCalled();
	} );
} );
