/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import nock from 'nock';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { StudentBulkActionButton } from './index';

jest.mock( '@wordpress/data', () =>
	require( '../../../../tests/mocks/wordpress-data' )( {
		useSelect: jest.fn(),
	} )
);

/**
 *  Create a custom screen selector that ignores text inside html tags like hello <strong> world</strong>
 *
 * @param {string} value Text value to be searched without tags. E.g. "Hello Word".
 * @return {Function} custom selector can be used by screen.getByText to match text with inline html.
 */

const ignoreInlineTags = ( value ) => ( _, node ) => {
	const hasText = ( n ) => n.textContent === value;
	const nodeHasText = hasText( node );
	const childrenDontHaveText = Array.from( node.children ).every(
		( child ) => ! hasText( child )
	);

	return nodeHasText && childrenDontHaveText;
};

let spy;
const courses = [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
];

// Create a bulk action selector with enrol student option selected.
beforeAll( () => {
	spy = jest.spyOn( document, 'getElementById' );
} );
describe( '<StudentBulkActionButton />', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( { courses, isFetching: false } );
	} );

	it( 'Should be disabled by default on render', () => {
		render( <StudentBulkActionButton /> );
		const button = screen.getByRole( 'button', {
			name: 'Select Action',
		} );
		expect( button ).toBeDisabled();
	} );

	it( 'Should render the `Add to course` modal', async () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment', selected: true },
			{ value: 'remove_progress' },
			{ value: 'remove_enrolment' },
		] );
		const { getByRole } = render(
			<StudentBulkActionButton isDisabled={ false } />
		);

		fireEvent.click(
			getByRole( 'button', {
				name: 'Select Action',
			} )
		);

		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to add 3 students to:'
				)
			)
		).toBeInTheDocument();
	} );

	it( 'Should render the `Reset Progress` modal', async () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress', selected: true },
			{ value: 'remove_enrolment' },
		] );
		const { getByRole } = render(
			<StudentBulkActionButton isDisabled={ false } />
		);

		fireEvent.click(
			getByRole( 'button', {
				name: 'Select Action',
			} )
		);

		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to reset progress from for 3 students:'
				)
			)
		).toBeInTheDocument();
	} );

	it( 'Should render the `Remove from Course` modal', async () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress' },
			{ value: 'remove_enrolment', selected: true },
		] );
		const { getByRole } = render(
			<StudentBulkActionButton isDisabled={ false } />
		);

		fireEvent.click(
			getByRole( 'button', {
				name: 'Select Action',
			} )
		);

		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to remove 3 students from:'
				)
			)
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'multiple students', [ 1, 3 ], '2 students' ],
		[ 'one student', [ 2 ], 'Student 2' ],
	] )(
		'Submits the students selected in the table for %s',
		async ( scenario, studentIds, description ) => {
			setupSelector(
				[ { value: 'enrol_restore_enrolment', selected: true } ],
				studentIds
			);
			const request = nock( 'http://localhost' )
				.post( '/sensei-internal/v1/course-students/batch', {
					student_ids: studentIds,
					course_ids: [ 1 ],
				} )
				.query( { _locale: 'user' } )
				.reply( 200, {
					1: { 1: true },
					2: { 1: true },
					3: { 1: true },
				} );
			render( <StudentBulkActionButton isDisabled={ false } /> );

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Select Action' } )
			);

			expect(
				screen.getByText(
					ignoreInlineTags(
						`Select the course(s) you would like to add ${ description } to:`
					)
				)
			).toBeInTheDocument();

			fireEvent.click( await screen.findByLabelText( 'My Course' ) );
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Add to Course' } )
			);

			await waitFor( () => expect( request.isDone() ).toBe( true ) );
		}
	);
} );

/**
 *  Create selector element with options that are passed to the function.
 *
 * @param {Array} options    Options to created selector with.
 * @param {Array} studentIds Selected student IDs.
 */
const setupSelector = ( options, studentIds = [ 1, 2, 3 ] ) => {
	render(
		<table>
			<tbody>
				{ [ 1, 2, 3 ].map( ( id ) => (
					<tr key={ id }>
						<td>
							<input
								type="checkbox"
								className="sensei_user_select_id"
								value={ id }
								defaultChecked={ studentIds.includes( id ) }
							/>
							<span
								className="student-action-menu"
								data-user-display-name={ `Student ${ id }` }
							/>
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);

	const mockSelector = document.createElement( 'select' );
	mockSelector.id = 'bulk-action-selector-top';
	options.forEach( ( option ) => {
		const optionElement = document.createElement( 'option' );
		optionElement.value = option.value;

		if ( option.selected ) {
			optionElement.selected = option.selected;
		}

		mockSelector.appendChild( optionElement );
	} );

	spy.mockImplementation( ( elementId ) => {
		return 'bulk-action-selector-top' === elementId
			? mockSelector
			: null;
	} );
};
