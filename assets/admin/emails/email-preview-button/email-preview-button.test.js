/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EmailPreviewButton } from './email-preview-button';

jest.mock( '@wordpress/data' );

const defaults = {
	previewLink: 'https://example.com/',
	postId: 1,
	isSaveable: true,
	isAutosaveable: true,
	isLocked: false,
	isDraft: false,
};

useSelect.mockReturnValue( defaults );
useDispatch.mockReturnValue( { autosave: jest.fn(), savePost: jest.fn() } );

describe( '<EmailPreviewButton />', () => {
	const { getByText, queryByText } = screen;

	beforeEach( () => {
		document.body.innerHTML = `<div class="block-editor-post-preview__dropdown" />`;
	} );

	it( 'Should display the preview button', async () => {
		render( <EmailPreviewButton /> );

		expect( getByText( 'Preview' ) ).toBeInTheDocument();
	} );

	it( "Shouldn't display the preview button when the container is missing", async () => {
		document.body.innerHTML = '';

		render( <EmailPreviewButton /> );

		expect( queryByText( 'Preview' ) ).toBeNull();
	} );

	it( 'Should open a new window when the button is clicked', async () => {
		global.open = jest.fn();

		render( <EmailPreviewButton /> );

		await act( async () => {
			userEvent.click( getByText( 'Preview' ) );
		} );

		expect( global.open ).toBeCalledWith(
			defaults.previewLink,
			'sensei-email-preview-' + defaults.postId
		);
	} );

	it( 'Should call autosave when the post is not a draft', async () => {
		const autosaveMock = jest.fn();

		useDispatch.mockReturnValue( { autosave: autosaveMock } );

		render( <EmailPreviewButton /> );

		await act( async () => {
			userEvent.click( getByText( 'Preview' ) );
		} );

		expect( autosaveMock ).toBeCalled();
	} );

	it( 'Should call savePost when the post is a draft', async () => {
		const savePostMock = jest.fn();

		useSelect.mockReturnValue( { ...defaults, isDraft: true } );
		useDispatch.mockReturnValue( { savePost: savePostMock } );

		render( <EmailPreviewButton /> );

		await act( async () => {
			userEvent.click( getByText( 'Preview' ) );
		} );

		expect( savePostMock ).toBeCalled();
	} );

	it( 'Should not save when not autosaveable', async () => {
		const autosaveMock = jest.fn();

		useSelect.mockReturnValue( { ...defaults, isAutosaveable: false } );
		useDispatch.mockReturnValue( { autosave: autosaveMock } );

		render( <EmailPreviewButton /> );

		await act( async () => {
			userEvent.click( getByText( 'Preview' ) );
		} );

		expect( autosaveMock ).not.toBeCalled();
	} );

	it( 'Should be disabled if not saveable', async () => {
		useSelect.mockReturnValue( { ...defaults, isSaveable: false } );

		render( <EmailPreviewButton /> );

		expect( getByText( 'Preview' ) ).toBeDisabled();
	} );
} );
