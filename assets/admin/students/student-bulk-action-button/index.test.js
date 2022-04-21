/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { StudentBulkActionButton } from './index';
import nock from 'nock';

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
		nock( 'http://localhost' )
			.persist()
			.get( '/wp-json/wp/v2/courses' )
			.query( { per_page: 100 } )
			.reply( 200, courses );
	} );
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
