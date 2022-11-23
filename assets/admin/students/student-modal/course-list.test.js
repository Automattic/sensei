/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CourseList } from './course-list';

const courses = [
	{
		id: 1,
		title: { rendered: 'Course 1' },
	},
	{
		id: 2,
		title: { rendered: 'Course 2' },
	},
	{
		id: 3,
		title: { rendered: 'Course 3' },
	},
];

jest.mock( '@wordpress/data' );

describe( '<CourseList />', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( { courses, isFetching: false } );
	} );

	it( 'Should display courses in the list', async () => {
		render( <CourseList /> );

		expect(
			await screen.findByLabelText( courses.at( 0 ).title.rendered )
		).toBeTruthy();
	} );

	it( 'Should call onChange with the selected courses when a course is selected', async () => {
		const onChange = jest.fn();

		render( <CourseList onChange={ onChange } /> );

		fireEvent.click(
			await screen.findByLabelText( courses.at( 0 ).title.rendered )
		);
		fireEvent.click(
			await screen.findByLabelText( courses.at( 2 ).title.rendered )
		);

		expect( onChange ).toHaveBeenCalledWith( [
			courses.at( 0 ),
			courses.at( 2 ),
		] );
	} );

	it( 'Should remove unselected items when a course is selected and deselected', async () => {
		const onChange = jest.fn();

		render( <CourseList onChange={ onChange } /> );

		fireEvent.click(
			await screen.findByLabelText( courses.at( 0 ).title.rendered )
		);
		fireEvent.click(
			await screen.findByLabelText( courses.at( 0 ).title.rendered )
		);

		fireEvent.click(
			await screen.findByLabelText( courses.at( 1 ).title.rendered )
		);

		expect( onChange ).toHaveBeenLastCalledWith( [ courses.at( 1 ) ] );
	} );

	describe( 'When there is no course', () => {
		beforeEach( () => {
			useSelect.mockReturnValue( { courses: [], isFetching: false } );
		} );

		it( 'Should show a message when there are no courses', async () => {
			render( <CourseList /> );

			expect(
				await screen.findByText( 'No courses found.' )
			).toBeTruthy();
		} );
	} );

	describe( 'When there are HTML-Entities in course titles', () => {
		beforeEach( () => {
			useSelect.mockReturnValue( {
				courses: [
					{
						id: 1,
						title: { rendered: 'Course&#8217;s' },
					},
				],
				isFetching: false,
			} );
		} );

		it( 'Should show the course title without HTML-Entities', async () => {
			render( <CourseList /> );

			expect( await screen.findByText( 'Course’s' ) ).toBeTruthy();
		} );
	} );
} );
