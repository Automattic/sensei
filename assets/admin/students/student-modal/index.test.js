/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import nock from 'nock';
/**
 * Internal dependencies
 */
import { StudentModal } from './index';

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

const students = [ 1, 2, 3 ];
const NONCE = 'some-nounce-id';

describe( '<StudentModal />', () => {
	const { getByText, getByRole, findByText, findByRole } = screen;

	beforeEach( () => {
		nock.disableNetConnect();
		nock( 'http://localhost' )
			.persist()
			.get( '/wp-json/wp/v2/courses' )
			.query( { per_page: 100 } )
			.reply( 200, courses );

		nock( 'http://localhost' )
			.persist()
			.get( '/wp-admin/admin-ajax.php' )
			.query( { action: 'rest-nonce' } )
			.reply( 200, NONCE );
	} );

	it( 'Should display a list of courses', async () => {
		render( <StudentModal action="add" /> );
		expect(
			await findByText( courses.at( 0 ).title.rendered )
		).toBeTruthy();
	} );

	describe( 'Add action', () => {
		const onClose = jest.fn();

		beforeEach( () => {
			render(
				<StudentModal
					action="add"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the action description', async () => {
			expect(
				getByText(
					'Select the course(s) you would like to add students to:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				getByRole( 'button', { name: 'Add to Course' } )
			).toBeTruthy();
		} );

		it( 'Should add the selected students to the selected course', async () => {
			nock( 'http://localhost' )
				.post( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-students/batch',
					_wpnonce: NONCE,
				} )
				.once()
				.reply( 200 );

			fireEvent.click(
				await screen.findByLabelText( courses.at( 0 ).title.rendered )
			);

			fireEvent.click(
				await findByRole( 'button', { name: 'Add to Course' } )
			);
			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a fail to add the students to the courses', () => {
			it( 'Should display an error message', async () => {
				nock( 'http://localhost' )
					.post( '/' )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_wpnonce: NONCE,
					} )
					.once()
					.reply( 500 );

				fireEvent.click(
					await findByRole( 'button', { name: 'Add to Course' } )
				);

				expect(
					screen.findByText( 'Sorry, something went wrong' )
				).toBeTruthy();
			} );
		} );
	} );

	describe( 'Remove action', () => {
		const onClose = jest.fn();
		beforeEach( async () => {
			render(
				<StudentModal
					action="remove"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the action description', async () => {
			expect(
				await findByText(
					'Select the course(s) you would like to remove students from:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await findByRole( 'button', { name: 'Remove from Course' } )
			).toBeTruthy();
		} );

		it( 'Should remove the selected students to the selected course', async () => {
			nock( 'http://localhost/' )
				.delete( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-students/batch',
					_wpnonce: NONCE,
				} )
				.once()
				.reply( 200 );

			fireEvent.click(
				await screen.findByLabelText( courses.at( 0 ).title.rendered )
			);

			fireEvent.click(
				await findByRole( 'button', { name: 'Remove from Course' } )
			);

			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a fail to remove the students from the courses', () => {
			it( 'Should display an error message', async () => {
				nock( 'http://localhost' )
					.delete( '/' )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_wpnonce: NONCE,
					} )
					.once()
					.reply( 500 );

				fireEvent.click(
					await findByRole( 'button', { name: 'Remove from Course' } )
				);

				expect(
					screen.findByText( 'Sorry, something went wrong' )
				).toBeTruthy();
			} );
		} );
	} );

	describe( 'Reset action', () => {
		const onClose = jest.fn();
		beforeEach( async () => {
			await act( async () =>
				render(
					<StudentModal
						action="reset-progress"
						onClose={ onClose }
						students={ students }
					/>
				)
			);
		} );

		it( 'Should display the action description', async () => {
			expect(
				getByText(
					'Select the course(s) you would like to reset or remove progress for:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await findByRole( 'button', {
					name: 'Reset or Remove Progress',
				} )
			).toBeTruthy();
		} );

		it( 'Should reset the selected the students progress from the selected courses', async () => {
			nock( 'http://localhost' )
				.delete( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-progress/batch',
					_wpnonce: NONCE,
				} )
				.once()
				.reply( 200 );

			fireEvent.click(
				await screen.findByLabelText( courses.at( 0 ).title.rendered )
			);

			fireEvent.click(
				await findByRole( 'button', {
					name: 'Reset or Remove Progress',
				} )
			);
			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a fail to reset the students progress', () => {
			it( 'Should display an error message', async () => {
				nock( 'http://localhost' )
					.delete( '/', {
						student_ids: students,
						course_ids: [ courses.at( 0 ).id ],
					} )
					.once()
					.query( {
						rest_route: '/sensei-internal/v1/course-progress/batch',
						_wpnonce: NONCE,
					} )
					.reply( 500 );

				fireEvent.click(
					await findByRole( 'button', {
						name: 'Reset or Remove Progress',
					} )
				);

				expect(
					screen.findByText( 'Sorry, something went wrong' )
				).toBeTruthy();
			} );
		} );
	} );
} );
