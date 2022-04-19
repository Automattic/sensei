/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { StudentsBulkActionButton } from './index';
import nock from 'nock';

let spy;
const courses = [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
];
const NOCK_HOST_URL = 'http://localhost';
const NONCE = 'some-nonce-id';

// Create a bulk action selector with enrol student option selected.
beforeAll( () => {
	spy = jest.spyOn( document, 'getElementById' );
} );
describe( '<StudentsBulkActionButton />', () => {
	beforeAll( () => {
		nock( NOCK_HOST_URL )
			.persist()
			.get( '/wp-json/wp/v2/courses' )
			.query( { per_page: 100 } )
			.reply( 200, courses );

		nock( NOCK_HOST_URL )
			.persist()
			.get( '/wp-admin/admin-ajax.php' )
			.query( { action: 'rest-nonce' } )
			.reply( 200, NONCE );

		const mockSelector = document.createElement( 'select' );
		mockSelector.id = 'bulk-action-selector-top';
		const option1 = document.createElement( 'option' );
		option1.value = 'enrol_restore_enrolment';
		option1.selected = true;
		mockSelector.appendChild( option1 );
		const option2 = document.createElement( 'option' );
		option2.value = 'remove_enrolment';
		mockSelector.appendChild( option2 );
		const option3 = document.createElement( 'option' );
		option3.value = 'remove_progress';
		mockSelector.appendChild( option3 );
		spy.mockReturnValue( mockSelector );
		const mockStudentIdContainer = document.createElement( 'input' );
		mockStudentIdContainer.id = 'bulk-action-user-ids';
		mockStudentIdContainer.value = '[1,2,3]';
		spy.mockReturnValue( mockStudentIdContainer );
	} );

	it( 'Student modal is rendered with action to add students on button click when add option is selected', () => {
		render( <StudentsBulkActionButton /> );
		// Click Select Courses button to open modal.
		const button = screen.getByRole( 'button', {
			id: 'sensei-bulk-learner-actions-modal-toggle',
		} );
		button.click();
		expect(
			screen.getByText( 'Select the course(s) you would like to add', {
				exact: false,
			} ).textContent
		).toEqual(
			'Select the course(s) you would like to add 3 students to:'
		);
	} );
} );
