/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { StudentsBulkActionButton } from './index';

let spy;
const coursePromise = Promise.resolve( [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
] );

// Mock fetch for student modal.
jest.mock( '@wordpress/api-fetch', () => jest.fn() );
apiFetch.mockImplementation( () => coursePromise );

// Create a bulk action selector with enrol student option selected.
beforeAll( () => {
	spy = jest.spyOn( document, 'getElementById' );
} );
describe( '<StudentsBulkActionButton />', () => {
	beforeAll( () => {
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
	} );

	it( 'Student modal is rendered with action to add students on button click when add option is selected', () => {
		render( <StudentsBulkActionButton /> );
		// Click Select Courses button to open modal.
		const button = screen.getByRole( 'button', {
			id: 'sensei-bulk-learner-actions-modal-toggle',
		} );
		button.click();
		expect(
			screen.getByText(
				'Select the course(s) you would like to add students to:'
			)
		).toBeTruthy();
	} );
} );
