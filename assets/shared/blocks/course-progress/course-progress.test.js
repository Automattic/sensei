/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CourseProgress from './index';

describe( '<CourseProgress />', () => {
	it( 'Should render the numbers and progress bar correctly', () => {
		const { queryByText, queryByRole, container } = render(
			<CourseProgress lessonsCount={ 10 } completedCount={ 5 } />
		);

		const completedText = container.querySelector(
			'.sensei-course-progress__completed'
		).innerHTML;

		expect( queryByText( '10 Lessons' ) ).toBeTruthy();

		expect( completedText ).toMatch( /5 Completed/ );
		expect( completedText ).toMatch( /50%/ );
		expect(
			queryByRole( 'progressbar' ).getAttribute( 'aria-valuenow' )
		).toEqual( '50' );
	} );

	it( 'Should render without the completed percentage', () => {
		const { queryByText } = render(
			<CourseProgress
				lessonsCount={ 10 }
				completedCount={ 5 }
				hidePercentage
			/>
		);

		expect( queryByText( '5 Completed' ) ).toBeTruthy();
	} );
} );
