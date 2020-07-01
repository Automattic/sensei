import { render, fireEvent } from '@testing-library/react';
import { DonePage } from './done-page';
const defaults = {
	fetchImportLog: () => {},
};
describe( '<DonePage />', () => {
	it( 'should load import log when opened.', () => {
		const mockFetchImportLog = jest.fn();
		render( <DonePage fetchImportLog={ mockFetchImportLog } /> );

		expect( mockFetchImportLog ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should allow restarting importer', () => {
		const mockRestartImporter = jest.fn();
		const { getByRole } = render(
			<DonePage restartImporter={ mockRestartImporter } { ...defaults } />
		);

		fireEvent.click(
			getByRole( 'button', { name: 'Import More Content' } )
		);
		expect( mockRestartImporter ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should show success icon', () => {
		const { container } = render(
			<DonePage
				results={ { question: { success: 4, error: 0 } } }
				{ ...defaults }
			/>
		);

		expect(
			container
				.querySelector( '.dashicon' )
				.classList.contains( 'dashicons-yes-alt' )
		).toBeTruthy();
	} );
	it( 'should show warning icon', () => {
		const { container } = render(
			<DonePage
				results={ { question: { success: 0, error: 4 } } }
				{ ...defaults }
			/>
		);

		expect(
			container
				.querySelector( '.dashicon' )
				.classList.contains( 'dashicons-warning' )
		).toBeTruthy();
	} );

	it( 'should show results summary', () => {
		const results = {
			question: { success: 4, error: 7 },
			course: { success: 2, error: 0 },
			lesson: { success: 0, error: 0 },
		};

		const { queryByText } = render(
			<DonePage results={ results } { ...defaults } />
		);

		const success = queryByText( 'The following content was imported:' )
			.nextSibling.textContent;

		expect( success ).toContain( '4 questions' );
		expect( success ).toContain( '2 courses' );
		expect( success ).not.toContain( 'lessons' );

		const errors = queryByText(
			( content, node ) =>
				node.textContent === 'The following content failed to import:'
		).nextSibling.textContent;

		expect( errors ).toContain( '7 questions' );
		expect( errors ).not.toContain( 'courses' );
		expect( errors ).not.toContain( 'lessons' );
	} );

	it( 'should show import log', () => {
		const results = {
			question: { success: 4, error: 1 },
			course: { success: 2, error: 0 },
			lesson: { success: 0, error: 1 },
		};
		const logs = {
			offset: 0,
			total: 2,
			items: [
				{
					type: 'question',
					line: 1,
					severity: 'error',
					descriptor: 'ID: 1',
					message: 'Question 1 Meta field invalid.',
				},
				{
					type: 'lesson',
					line: 7,
					severity: 'error',
					descriptor: 'ID: 7',
					message: 'Lesson 7 Invalid.',
				},
			],
		};

		const { getByRole } = render(
			<DonePage logs={ logs } results={ results } { ...defaults } />
		);

		fireEvent.click( getByRole( 'button', { name: 'View Import Log' } ) );

		const table = getByRole( 'button', { name: 'View Import Log' } )
			.nextSibling;

		const rows = table.querySelectorAll( 'tr' );
		expect( rows[ 1 ].textContent ).toMatch(
			[
				'ID: 1',
				'Questions, Line: 1',
				'Question 1 Meta field invalid.',
			].join( '' )
		);
		expect( rows[ 2 ].textContent ).toMatch(
			[ 'ID: 7', 'Lessons, Line: 7', 'Lesson 7 Invalid.' ].join( '' )
		);
	} );
} );
