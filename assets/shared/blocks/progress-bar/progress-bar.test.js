/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProgressBar from './index';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn(),
} ) );

describe( '<ProgressBar />', () => {
	it( 'Should render the numbers and progress bar correctly', () => {
		const { queryByRole, container } = render(
			<ProgressBar totalCount={ 10 } completedCount={ 5 } />
		);
		const completedText = container.querySelector(
			'.sensei-progress-bar__label'
		).innerHTML;

		expect( completedText ).toMatch( '5 of 10  completed (50%)' );
		expect(
			queryByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
	} );

	it( 'Should render without the completed percentage', () => {
		const { container } = render(
			<ProgressBar
				totalCount={ 10 }
				completedCount={ 5 }
				hidePercentage
			/>
		);
		const completedText = container.querySelector(
			'.sensei-progress-bar__label'
		).innerHTML;
		expect( completedText ).toMatch( '5 of 10  completed' );
	} );
} );
