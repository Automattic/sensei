/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CourseProgress from './course-progress';

describe( '<CourseProgress />', () => {
	it( 'Should render the numbers and progress bar correctly', () => {
		const { queryByText, queryByRole } = render(
			<CourseProgress lessons={ 10 } completed={ 5 } />
		);

		expect( queryByText( '10 Lessons' ) ).toBeTruthy();
		expect( queryByText( '5 Completed' ) ).toBeTruthy();
		expect(
			queryByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
	} );
} );
