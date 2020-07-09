import { render, fireEvent } from '@testing-library/react';
import { DonePage } from './done-page';

describe( '<DonePage />', () => {
	it( 'should allow restarting importer', () => {
		const mockRestartImporter = jest.fn();
		const { getByRole } = render(
			<DonePage restartImporter={ mockRestartImporter } />
		);

		fireEvent.click(
			getByRole( 'button', { name: 'Import More Content' } )
		);
		expect( mockRestartImporter ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should show completed summary', () => {
		const successResults = [
			{
				count: 4,
				key: 'question',
			},
			{
				count: 2,
				key: 'course',
			},
			{
				count: 0,
				key: 'lesson',
			},
		];

		const { queryByText } = render(
			<DonePage successResults={ successResults } />
		);

		const success = queryByText( 'The following content was imported:' )
			.nextSibling.textContent;

		expect( success ).toContain( '4 questions' );
		expect( success ).toContain( '2 courses' );
		expect( success ).toContain( '0 lessons' );
	} );

	it( 'should show import log', () => {
		const logs = {
			error: [
				{
					type: 'lesson',
					title: 'Lesson title',
					line: 7,
					severity: 'error',
					descriptor: 'ID: 7',
					message: 'Error message.',
				},
			],
			notice: [
				{
					type: 'question',
					title: 'Question title',
					line: 1,
					severity: 'notice',
					descriptor: 'ID: 1',
					message: 'Warning message.',
				},
			],
		};

		const { getByText, container } = render( <DonePage logs={ logs } /> );

		expect( getByText( 'Failed' ) ).toBeTruthy();
		expect( getByText( 'Partial' ) ).toBeTruthy();

		const rows = container.querySelectorAll(
			'.sensei-import-done__log-data tbody tr'
		);
		expect( rows[ 0 ].textContent ).toMatch(
			[ 'Lessons', 'Lesson title', '7', 'Error message.' ].join( '' )
		);
		expect( rows[ 1 ].textContent ).toMatch(
			[ 'Question title', '1', 'Warning message.' ].join( '' )
		);
	} );

	it( 'should show fetching state', () => {
		const { getByText } = render( <DonePage isFetching /> );

		expect( getByText( 'Fetching log details…' ) ).toBeTruthy();
	} );

	it( 'should show error message', () => {
		const fetchError = {
			message: 'Error message.',
		};
		const { getAllByText } = render(
			<DonePage fetchError={ fetchError } />
		);

		expect( getAllByText( /Error message./ ) ).toBeTruthy();
	} );
} );
