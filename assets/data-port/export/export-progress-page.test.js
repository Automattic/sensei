import { render, fireEvent } from '@testing-library/react';
import { ExportProgressPage } from './export-progress-page';

describe( '<ExportProgressPage />', () => {
	it( 'shows progress bar until ready', () => {
		const { getByRole } = render(
			<ExportProgressPage
				job={ { status: 'progress', percentage: 20 } }
			/>
		);

		expect( getByRole( 'progressbar' ).getAttribute( 'value' ) ).toEqual(
			'20'
		);
	} );

	it( 'prompts downloads', () => {
		const onClick = jest.fn();
		window.addEventListener( 'click', onClick );
		render(
			<ExportProgressPage
				job={ {
					status: 'completed',
					files: [
						{ url: '/test.csv', name: 'test.csv' },
						{ url: '/test-2.csv', name: 'test-2.csv' },
					],
				} }
			/>
		);

		expect( onClick ).toHaveBeenCalledTimes( 2 );
		expect( onClick ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( {
				target: expect.objectContaining( { download: 'test.csv' } ),
			} )
		);
		expect( onClick ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( {
				target: expect.objectContaining( { download: 'test-2.csv' } ),
			} )
		);
	} );

	it( 'lists exported files', () => {
		const { getByRole } = render(
			<ExportProgressPage
				job={ {
					status: 'completed',
					files: [ { url: '/test.csv', name: 'test.csv' } ],
				} }
			/>
		);

		expect( getByRole( 'link', { text: 'test.csv' } ) ).toBeTruthy();
	} );

	it( 'allows resetting export', () => {
		const onReset = jest.fn();
		const { getByRole } = render(
			<ExportProgressPage
				job={ {
					status: 'completed',
				} }
				reset={ onReset }
			/>
		);
		fireEvent.click(
			getByRole( 'button', { name: 'Export More Content' } )
		);
		expect( onReset ).toHaveBeenCalled();
	} );

	it( 'shows errors', () => {
		const { getByText } = render(
			<ExportProgressPage
				job={ { status: 'completed', error: 'Error occured' } }
			/>
		);

		expect( getByText( 'Error occured' ) ).toBeTruthy();
	} );
} );
