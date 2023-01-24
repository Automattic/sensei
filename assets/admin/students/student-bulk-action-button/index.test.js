/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { StudentBulkActionButton } from './index';

jest.mock( '@wordpress/data' );

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
	const selectActionButton = () =>
		screen.getByRole( 'button', {
			name: 'Select Action',
		} );

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

	it( 'Should render the `Add to course` modal', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment', selected: true },
			{ value: 'remove_progress' },
			{ value: 'remove_enrolment' },
		] );
		render( <StudentBulkActionButton isDisabled={ false } /> );

		selectActionButton().click();
		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to add 3 students to:'
				)
			)
		).toBeInTheDocument();
	} );

	it( 'Should render the `Reset Progress` modal', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress', selected: true },
			{ value: 'remove_enrolment' },
		] );
		render( <StudentBulkActionButton isDisabled={ false } /> );

		selectActionButton().click();

		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to reset progress from for 3 students:'
				)
			)
		).toBeInTheDocument();
	} );
	it( 'Should render the `Remove from Course` modal', () => {
		setupSelector( [
			{ value: 'enrol_restore_enrolment' },
			{ value: 'remove_progress' },
			{ value: 'remove_enrolment', selected: true },
		] );
		render( <StudentBulkActionButton isDisabled={ false } /> );

		selectActionButton().click();
		expect(
			screen.getByText(
				ignoreInlineTags(
					'Select the course(s) you would like to remove 3 students from:'
				)
			)
		).toBeInTheDocument();
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

	const mockStudentIdContainer = document.createElement( 'input' );
	mockStudentIdContainer.id = 'bulk-action-user-ids';
	mockStudentIdContainer.value = '[1,2,3]';

	spy.mockImplementation( ( elementId ) => {
		return 'bulk-action-selector-top' === elementId
			? mockSelector
			: mockStudentIdContainer;
	} );
};
