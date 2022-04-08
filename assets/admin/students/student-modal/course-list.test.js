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
		nock.cleanAll();
		nock( 'http://localhost' )
			.get( '/wp/v2/courses' )
			.once()
			.query( { per_page: 100, _locale: 'user' } )
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

	describe( 'when there is no course', () => {
		beforeEach( () => {
			nock.cleanAll();
			nock( 'http://localhost' )
				.get( '/wp/v2/courses' )
				.query( { per_page: 100, _locale: 'user' } )
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

	describe( 'when a course is selected', () => {
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

		describe( 'when a course is selected and deselected', () => {
			it( 'Should remove unselected items', async () => {
				const onChange = jest.fn();
				await act( async () => {
					render( <CourseList onChange={ onChange } /> );
				} );

				const newLocal = fireEvent.click;
				newLocal(
					await screen.findByLabelText(
						courses.at( 0 ).title.rendered
					)
				);
				fireEvent.click(
					await screen.findByLabelText(
						courses.at( 0 ).title.rendered
					)
				);

				fireEvent.click(
					await screen.findByLabelText(
						courses.at( 1 ).title.rendered
					)
				);

				expect( onChange ).toHaveBeenLastCalledWith( [
					courses.at( 1 ),
				] );
			} );
		} );
	} );
} );
