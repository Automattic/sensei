/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { CourseCategoryEdit } from './course-categories-edit';
import useCourseCategories from './hooks/use-course-categories';

const message =
	'The Course Categories block can only be used inside the Course List block.';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn( ( params ) => params ),
	InspectorControls: ( { children } ) => <>{ children }</>,
	ContrastChecker: () => <h2>Contrast Checker</h2>,
	PanelColorSettings: ( props ) => (
		<>
			<h1>{ props.title } </h1> { props.children }
		</>
	),
	Warning: () => <div>{ message }</div>,
	withColors: () => ( Component ) => Component,
} ) );

jest.mock( './hooks/use-course-categories' );

const attributes = {
	textAlign: 'left',
};

const context = {
	postId: 'some-post-id',
	postType: 'course',
};

const categories = [
	{
		id: 1,
		link: 'http://www.example.com',
		name: 'course category A',
	},
	{
		id: 2,
		link: 'http://www.example.com',
		name: 'course category B',
	},
];

describe( 'CourseCategoryEdit', () => {
	it( 'should render the categories', () => {
		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: true,
			postTerms: categories,
		} );

		const { getByText } = render(
			<CourseCategoryEdit
				clientId="some-client-id"
				attributes={ attributes }
				context={ context }
				setAttributes={ () => {} }
			/>
		);
		categories.forEach( ( category ) =>
			expect( getByText( category.name ) ).toBeInTheDocument()
		);
	} );

	it( 'should render an error', () => {
		const { getByText } = render(
			<CourseCategoryEdit
				clientId="some-client-id"
				attributes={ attributes }
				context={ {
					postId: 'some-post-id',
					postType: 'page',
				} }
				setAttributes={ () => {} }
			/>
		);

		expect( getByText( message ) ).toBeInTheDocument();
	} );

	it( 'should not fallback the colors', () => {
		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: true,
			postTerms: categories,
		} );

		const { container } = render(
			<CourseCategoryEdit
				clientId="some-client-id"
				attributes={ attributes }
				context={ context }
				setAttributes={ () => {} }
				defaultBackgroundColor={ { color: 'some-color' } }
				defaultTextColor={ { color: 'some-color' } }
			/>
		);

		expect( container.firstChild ).toHaveClass( 'has-background' );
		expect( container.firstChild ).toHaveClass( 'has-text-color' );
	} );

	it( 'should fallback the colors', () => {
		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: true,
			postTerms: categories,
		} );

		const { container } = render(
			<CourseCategoryEdit
				clientId="some-client-id"
				attributes={ attributes }
				context={ context }
				setAttributes={ () => {} }
				defaultBackgroundColor={ null }
				defaultTextColor={ null }
			/>
		);

		expect( container.firstChild ).not.toHaveClass( 'has-background' );
		expect( container.firstChild ).not.toHaveClass( 'has-text-color' );
	} );
} );
