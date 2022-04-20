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
import { StudentBulkActionButton } from './index';

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
describe( '<StudentBulkActionButton />', () => {
	it( 'Student modal is rendered with action to add students on button click when add option is selected', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment', selected: true },
			{ value: 'remove_progress' },
			{ value: 'remove_progress' },
		] );
		render( <StudentBulkActionButton /> );

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

	it( 'Student modal is rendered with action to remove progress on button click when remove progress is selected', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress', selected: true },
			{ value: 'remove_progress' },
		] );
		render( <StudentBulkActionButton /> );

		// Click Select Courses button to open modal.
		const button = screen.getByRole( 'button', {
			id: 'sensei-bulk-learner-actions-modal-toggle',
		} );
		button.click();
		expect(
			screen.getByText(
				'Select the course(s) you would like to reset or remove progress for:'
			)
		).toBeTruthy();
	} );
	it( 'Student modal is rendered with action to remove students on button click when remove from course is selected', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress' },
			{ value: 'remove_enrolment', selected: true },
		] );
		render( <StudentBulkActionButton /> );

		// Click Select Courses button to open modal.
		const button = screen.getByRole( 'button', {
			id: 'sensei-bulk-learner-actions-modal-toggle',
		} );
		button.click();
		expect(
			screen.getByText(
				'Select the course(s) you would like to remove students from:'
			)
		).toBeTruthy();
	} );
} );

/**
 *  Create selector element with options that are passed to the function.
 *
 * @param {Array} options Options to created selector with.
 */
const setupSelector = ( options ) => {
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
	spy.mockReturnValue( mockSelector );
};
