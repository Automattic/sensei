/**
 * External dependencies
 */
import { render, fireEvent, waitFor, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { replacePlaceholders, useWizardOpenState } from './helpers';

describe( 'replacePlaceholders', () => {
	const replaces = {
		title: 'New title',
		description: 'New description',
	};

	it( 'Should replace the placeholder content properly', () => {
		const blocks = [
			{
				attributes: {
					className: 'title',
					content: 'Title placeholder',
				},
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'Description placeholder',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const expectedBlocks = [
			{
				attributes: { className: 'title', content: 'New title' },
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'New description',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const newBlocks = replacePlaceholders( blocks, replaces );

		expect( newBlocks ).toEqual( expectedBlocks );
	} );
} );

describe( 'useWizardOpenState', () => {
	const TestComponent = () => {
		const [ open, setDone ] = useWizardOpenState();
		return (
			<div>
				{ open ? 'open' : 'closed' }
				<button onClick={ () => setDone( true ) }>done</button>
			</div>
		);
	};

	beforeAll( () => {
		jest.useFakeTimers();
	} );

	it( 'Should start open when no other modal is open', () => {
		const { queryByText } = render( <TestComponent /> );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( queryByText( 'open' ) ).toBeTruthy();
	} );

	it.skip( 'Should open when other modals get closed', async () => {
		document.body.classList.add( 'modal-open' );

		const { queryByText } = render( <TestComponent /> );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( queryByText( 'closed' ) ).toBeTruthy();

		document.body.classList.remove( 'modal-open' );
		await waitFor( () => {
			expect( queryByText( 'open' ) ).toBeTruthy();
		} );
	} );

	it( 'Should be closed when wizard is done', async () => {
		const { queryByText } = render( <TestComponent /> );

		fireEvent.click( queryByText( 'done' ) );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( queryByText( 'closed' ) ).toBeTruthy();
	} );
} );
