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

	it( 'should show message saying that no content was imported', () => {
		const { queryByText } = render( <DonePage successResults={ [] } /> );

		expect(
			queryByText( 'The following content was imported:' )
		).toBeFalsy();
		expect( queryByText( 'No content was imported.' ) ).toBeTruthy();
	} );

	it( 'should show import log', () => {
		const logs = {
			error: [
				{
					type: 'lesson',
					line: 7,
					severity: 'error',
					descriptor: 'ID: 7',
					message: 'Error message.',
					post: {
						title: null,
						edit_link: null,
					},
				},
			],
			notice: [
				{
					type: 'question',
					line: 1,
					severity: 'notice',
					descriptor: 'ID: 1',
					message: 'Warning message.',
					post: {
						title: 'Question title 1',
						edit_link: 'http://test.com/',
					},
				},
				{
					type: 'question',
					line: 2,
					severity: 'notice',
					descriptor: 'ID: 1',
					message: 'Warning message.',
					post: {
						title: 'Question title 2',
						edit_link: null,
					},
				},
			],
		};

		const { getByText, container } = render( <DonePage logs={ logs } /> );

		expect( getByText( 'Failed' ) ).toBeTruthy();
		expect( getByText( 'Partial' ) ).toBeTruthy();
		expect(
			getByText( 'Question title 1' ).getAttribute( 'href' )
		).toEqual( 'http://test.com/' );

		const rows = container.querySelectorAll(
			'.sensei-import-done__log-data tbody tr'
		);
		expect( rows[ 0 ].textContent ).toMatch(
			[ 'Lessons', 'No title', '7', 'Error message.' ].join( '' )
		);
		expect( rows[ 1 ].textContent ).toMatch(
			[ 'Question title 1', '1', 'Warning message.' ].join( '' )
		);
		expect( rows[ 2 ].textContent ).toMatch(
			[ 'Question title 2', '2', 'Warning message.' ].join( '' )
		);
	} );

	it( 'should show fetching state', () => {
		const { getByText } = render( <DonePage isFetching /> );

		expect( getByText( 'Fetching log details…' ) ).toBeTruthy();
	} );

	it( 'should show error message', () => {
		const retryMock = jest.fn();
		const fetchError = {
			message: 'Error message.',
		};
		const { getAllByText } = render(
			<DonePage fetchError={ fetchError } retry={ retryMock } />
		);

		expect( getAllByText( /Error message./ ) ).toBeTruthy();

		fireEvent.click( getAllByText( 'Retry' )[ 0 ] );
		expect( retryMock ).toBeCalled();
	} );
} );
