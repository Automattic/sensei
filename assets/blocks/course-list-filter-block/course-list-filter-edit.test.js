/**
 * External dependencies
 */
import { render, within } from '@testing-library/react';
/**
 * Internal dependencies
 */
import CourseListFilter from './course-list-filter-edit';
/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

const message =
	'The Course List Filter block can only be used inside the Course List block.';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn(),
	InspectorControls: ( { children } ) => <>{ children }</>,
	withColors: () => ( Component ) => Component,
	Warning: () => <div>{ message }</div>,
} ) );

const context = {
	query: { postType: 'course' },
};

const categories = [
	{
		id: 1,
		name: 'course category A',
	},
	{
		id: 2,
		name: 'course category B',
	},
];

jest.mock( '@wordpress/data' );

describe( 'CourseListFilterBlockEdit', () => {
	it( 'should render the dropdown for category filter', () => {
		useSelect.mockReturnValue( categories );
		const { getByText } = render(
			<CourseListFilter
				clientId="some-client-id"
				attributes={ { types: [ 'categories' ] } }
				context={ context }
			/>
		);
		categories.forEach( ( category ) =>
			expect( getByText( category.name ) ).toBeInTheDocument()
		);
	} );

	it( 'should render the dropdown for featured filter', () => {
		const { getByRole } = render(
			<CourseListFilter
				clientId="some-client-id"
				attributes={ { types: [ 'featured' ] } }
				context={ context }
			/>
		);
		expect(
			within( getByRole( 'combobox' ) ).getByText( 'Featured' )
		).toBeInTheDocument();
	} );

	it( 'should render the dropdown for student course filter', () => {
		const { getByRole } = render(
			<CourseListFilter
				clientId="some-client-id"
				attributes={ { types: [ 'student_course' ] } }
				context={ context }
			/>
		);
		expect(
			within( getByRole( 'combobox' ) ).getByText( 'Completed' )
		).toBeInTheDocument();
	} );

	it( 'should render an error', () => {
		const { getByText } = render(
			<CourseListFilter
				clientId="some-client-id"
				attributes={ { types: [ 'activity' ] } }
				context={ { query: { postType: 'post' } } }
				setAttributes={ jest.fn() }
			/>
		);

		expect( getByText( message ) ).toBeInTheDocument();
	} );
} );
