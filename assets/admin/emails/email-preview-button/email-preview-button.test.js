/**
 * External dependencies
 */
import { fireEvent, render, screen, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { EmailPreviewButton } from './email-preview-button';

const defaultProps = {
	previewLink: 'https://example.com/',
	postId: 1,
	isSaveable: true,
	isAutosaveable: true,
	isLocked: false,
	isDraft: false,
	autosave: jest.fn(),
	savePost: jest.fn(),
};

describe( '<EmailPreviewButton />', () => {
	const { getByText, queryByText } = screen;

	beforeEach( () => {
		document.body.innerHTML = `<div class="block-editor-post-preview__dropdown" />`;
	} );

	it( 'Should display the preview button', async () => {
		render( <EmailPreviewButton { ...defaultProps } /> );

		expect( getByText( 'Preview' ) ).toBeInTheDocument();
	} );

	it( "Shouldn't display the preview button when the container is missing", async () => {
		document.body.innerHTML = '';

		render( <EmailPreviewButton { ...defaultProps } /> );

		expect( queryByText( 'Preview' ) ).toBeNull();
	} );

	it( 'Should open a new window when the button is clicked', async () => {
		global.open = jest.fn();

		render( <EmailPreviewButton { ...defaultProps } /> );

		await act( async () => {
			fireEvent.click( getByText( 'Preview' ) );
		} );

		expect( global.open ).toBeCalledWith(
			defaultProps.previewLink,
			'sensei-email-preview-' + defaultProps.postId
		);
	} );

	it( 'Should call autosave when the post is not a draft', async () => {
		const autosaveMock = jest.fn();

		render(
			<EmailPreviewButton { ...defaultProps } autosave={ autosaveMock } />
		);

		await act( async () => {
			fireEvent.click( getByText( 'Preview' ) );
		} );

		expect( autosaveMock ).toBeCalled();
	} );

	it( 'Should call savePost when the post is a draft', async () => {
		const savePostMock = jest.fn();

		render(
			<EmailPreviewButton
				{ ...defaultProps }
				isDraft={ true }
				savePost={ savePostMock }
			/>
		);

		await act( async () => {
			fireEvent.click( getByText( 'Preview' ) );
		} );

		expect( savePostMock ).toBeCalled();
	} );

	it( 'Should not save when not autosaveable', async () => {
		const autosaveMock = jest.fn();

		render(
			<EmailPreviewButton
				{ ...defaultProps }
				isAutosaveable={ false }
				autosave={ autosaveMock }
			/>
		);

		await act( async () => {
			fireEvent.click( getByText( 'Preview' ) );
		} );

		expect( autosaveMock ).not.toBeCalled();
	} );

	it( 'Should be disabled if not saveable', async () => {
		render(
			<EmailPreviewButton { ...defaultProps } isSaveable={ false } />
		);

		expect( getByText( 'Preview' ) ).toBeDisabled();
	} );
} );
