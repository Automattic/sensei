/**
 * External dependencies
 */
import { act, render, screen, fireEvent } from '@testing-library/react';
import nock from 'nock';

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

describe( '<CourseList />', () => {
	beforeEach( () => {
		nock( 'http://localhost' )
			.persist()
			.get( '/wp-json/wp/v2/courses' )
			.query( { per_page: 100 } )
			.reply( 200, courses );
	} );

	it( 'Should display courses in the list', async () => {
		await act( async () => {
			render( <CourseList /> );
		} );
		expect(
			await screen.findByLabelText( courses.at( 0 ).title.rendered )
		).toBeTruthy();
	} );

	it( 'Should call onChange with the selected courses when a course is selected', async () => {
		const onChange = jest.fn();
		await act( async () => {
			render( <CourseList onChange={ onChange } /> );
		} );

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

	describe( 'when there is no course', () => {
		beforeEach( () => {
			nock.cleanAll();
			nock( 'http://localhost' )
				.get( '/wp-json/wp/v2/courses' )
				.query( { per_page: 100 } )
				.once()
				.reply( 200, [] );
		} );

		it( 'Should show a message when there are no courses', async () => {
			await act( async () => {
				render( <CourseList /> );
			} );

			expect(
				await screen.findByText( 'No courses found.' )
			).toBeTruthy();
		} );
	} );
} );
