/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
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
const NONCE = 'some-nonce-id';

describe( '<StudentModal />', () => {
	const { getByText, findByText, findByRole, findByLabelText } = screen;

	const courseOptionAt = async ( index ) =>
		findByLabelText( courses.at( index ).title.rendered );

	const buttonByLabel = async ( label ) =>
		findByRole( 'button', { name: label } );

	beforeAll( () => {
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
	afterAll( () => nock.cleanAll() );

	it( 'Should display a list of courses', async () => {
		render( <StudentModal action="add" /> );
		expect( await courseOptionAt( 0 ) ).toBeInTheDocument();
	} );

	it( 'Should disable the action button when there is no course selected', async () => {
		render( <StudentModal action="add" /> );
		expect( await buttonByLabel( 'Add to Course' ) ).toBeDisabled();
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
			).toBeInTheDocument();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await buttonByLabel( 'Add to Course' )
			).toBeInTheDocument();
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

			fireEvent.click( await courseOptionAt( 0 ) );

			fireEvent.click( await buttonByLabel( 'Add to Course' ) );

			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a failure to add the students to the courses', () => {
			beforeEach( async () => {
				nock( 'http://localhost' )
					.post( '/' )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_wpnonce: NONCE,
					} )
					.once()
					.reply( 500 );

				fireEvent.click( await courseOptionAt( 0 ) );

				fireEvent.click( await buttonByLabel( 'Add to Course' ) );
			} );

			it( 'Should display an error message', async () => {
				expect(
					await findByText( 'Sorry, something went wrong' )
				).toBeInTheDocument();
			} );

			it( 'Should enable the action button', async () => {
				expect(
					await buttonByLabel( 'Add to Course' )
				).toHaveAttribute( 'disabled', '' );
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
			).toBeInTheDocument();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await buttonByLabel( 'Remove from Course' )
			).toBeInTheDocument();
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

			fireEvent.click( await courseOptionAt( 0 ) );

			fireEvent.click( await buttonByLabel( 'Remove from Course' ) );

			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a failure to remove the students from the courses', () => {
			beforeEach( async () => {
				nock( 'http://localhost' )
					.delete( '/', {
						student_ids: students,
						course_ids: [ courses.at( 0 ).id ],
					} )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_wpnonce: NONCE,
					} )
					.once()
					.reply( 500 );

				fireEvent.click( await courseOptionAt( 0 ) );

				fireEvent.click( await buttonByLabel( 'Remove from Course' ) );
			} );

			it( 'Should display an error message', async () => {
				expect(
					await findByText( 'Sorry, something went wrong' )
				).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Reset/Remove Progress action', () => {
		const onClose = jest.fn();
		beforeEach( () => {
			render(
				<StudentModal
					action="reset-progress"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the action description', async () => {
			expect(
				getByText(
					'Select the course(s) you would like to reset or remove progress for:'
				)
			).toBeInTheDocument();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await buttonByLabel( 'Reset or Remove Progress' )
			).toBeInTheDocument();
		} );

		it( "Should reset the selected the students's progress from the selected courses", async () => {
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

			fireEvent.click( await courseOptionAt( 0 ) );

			fireEvent.click(
				await buttonByLabel( 'Reset or Remove Progress' )
			);
			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a failure to reset the students progress', () => {
			beforeEach( async () => {
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
					.reply( 500 );

				fireEvent.click( await courseOptionAt( 0 ) );
				fireEvent.click(
					await buttonByLabel( 'Reset or Remove Progress' )
				);
			} );

			it( 'Should display an error message', async () => {
				expect(
					await findByText( 'Sorry, something went wrong' )
				).toBeInTheDocument();
			} );
		} );
	} );
} );
