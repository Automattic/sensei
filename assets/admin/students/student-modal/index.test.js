/**
 * External dependencies
 */
import {
	fireEvent,
	render,
	screen,
	waitFor,
	cleanup,
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
const studentName = 'testname';
const NOCK_HOST_URL = 'http://localhost';

describe( '<StudentModal />', () => {
	const { getByText, findByText, findByRole, findByLabelText } = screen;

	const courseOptionAt = async ( index ) =>
		findByLabelText( courses.at( index ).title.rendered );

	const buttonByLabel = async ( label ) =>
		findByRole( 'button', { name: label } );

	beforeAll( () => {
		nock.disableNetConnect();
		nock( NOCK_HOST_URL )
			.persist()
			.get( '/wp/v2/courses' )
			.query( { per_page: 100, _locale: 'user' } )
			.reply( 200, courses );
	} );
	afterAll( () => nock.cleanAll() );

	it( 'Should display a list of courses', async () => {
		render( <StudentModal students={ students } action="add" /> );
		expect( await courseOptionAt( 0 ) ).toBeInTheDocument();
	} );

	it( 'Should disable the action button when there is no course selected', async () => {
		render( <StudentModal students={ students } action="add" /> );
		expect( await buttonByLabel( 'Add to Course' ) ).toBeDisabled();
	} );

	describe( 'Add action', () => {
		const onClose = jest.fn();
		const descriptionLookupText =
			'Select the course(s) you would like to add ';

		beforeEach( () => {
			render(
				<StudentModal
					action="add"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the description with student name for single input', async () => {
			cleanup();
			render(
				<StudentModal
					action="add"
					students={ students.slice( 0, 1 ) }
					studentName={ studentName }
				/>
			);
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + studentName + ' to:' );
		} );

		it( 'Should display the action description for multiple students', async () => {
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + '3 students to:' );
		} );

		it( 'Should add the selected students to the selected course', async () => {
			nock( NOCK_HOST_URL )
				.post( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-students/batch',
					_locale: 'user',
				} )
				.once()
				.reply( 200, { status: 'ok' } );

			fireEvent.click( await courseOptionAt( 0 ) );

			fireEvent.click( await buttonByLabel( 'Add to Course' ) );

			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a failure to add the students to the courses', () => {
			beforeEach( async () => {
				nock( NOCK_HOST_URL )
					.post( '/' )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_locale: 'user',
					} )
					.once()
					.reply( 500, { status: 'error' } );

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
		const descriptionLookupText =
			'Select the course(s) you would like to remove ';

		beforeEach( async () => {
			render(
				<StudentModal
					action="remove"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the description with student name for single input', async () => {
			cleanup();
			render(
				<StudentModal
					action="remove"
					students={ students.slice( 0, 1 ) }
					studentName={ studentName }
				/>
			);
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + studentName + ' from:' );
		} );

		it( 'Should display the action description for multiple students', async () => {
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + '3 students from:' );
		} );

		it( 'Should display the action button', async () => {
			expect(
				await buttonByLabel( 'Remove from Course' )
			).toBeInTheDocument();
		} );

		it( 'Should remove the selected students to the selected course', async () => {
			nock( NOCK_HOST_URL + '/' )
				.post( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-students/batch',
					_locale: 'user',
				} )
				.matchHeader( 'x-http-method-override', 'DELETE' )
				.once()
				.reply( 200, { status: 'ok' } );

			fireEvent.click( await courseOptionAt( 0 ) );

			fireEvent.click( await buttonByLabel( 'Remove from Course' ) );

			await waitFor( () => {
				expect( onClose ).toHaveBeenCalledWith( true );
			} );
		} );

		describe( 'when there is a failure to remove the students from the courses', () => {
			beforeEach( async () => {
				nock( NOCK_HOST_URL )
					.post( '/', {
						student_ids: students,
						course_ids: [ courses.at( 0 ).id ],
					} )
					.query( {
						rest_route: '/sensei-internal/v1/course-students/batch',
						_locale: 'user',
					} )
					.matchHeader( 'x-http-method-override', 'DELETE' )
					.once()
					.reply( 500, { status: 'error' } );

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
		const descriptionLookupText =
			'Select the course(s) you would like to reset or remove progress from for ';

		beforeEach( () => {
			render(
				<StudentModal
					action="reset-progress"
					onClose={ onClose }
					students={ students }
				/>
			);
		} );

		it( 'Should display the description with student name for single input', async () => {
			cleanup();
			render(
				<StudentModal
					action="reset-progress"
					students={ students.slice( 0, 1 ) }
					studentName={ studentName }
				/>
			);
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + studentName + ':' );
		} );

		it( 'Should display the action description for multiple students', async () => {
			expect(
				getByText( descriptionLookupText, { exact: false } ).textContent
			).toEqual( descriptionLookupText + '3 students:' );
		} );

		it( 'Should display the action button', async () => {
			expect(
				await buttonByLabel( 'Reset or Remove Progress' )
			).toBeInTheDocument();
		} );

		it( "Should reset the selected the students's progress from the selected courses", async () => {
			nock( NOCK_HOST_URL )
				.post( '/', {
					student_ids: students,
					course_ids: [ courses.at( 0 ).id ],
				} )
				.query( {
					rest_route: '/sensei-internal/v1/course-progress/batch',
					_locale: 'user',
				} )
				.matchHeader( 'x-http-method-override', 'DELETE' )
				.once()
				.reply( 200, { status: 'ok' } );

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
				nock( NOCK_HOST_URL )
					.post( '/', {
						student_ids: students,
						course_ids: [ courses.at( 0 ).id ],
					} )
					.query( {
						rest_route: '/sensei-internal/v1/course-progress/batch',
						_locale: 'user',
					} )
					.matchHeader( 'x-http-method-override', 'DELETE' )
					.once()
					.reply( 500, { status: 'error' } );

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
