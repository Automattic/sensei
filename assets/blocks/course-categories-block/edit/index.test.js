/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { CourseCategoryEdit } from '.';
import useColors from '../hooks/use-colors';
import useCourseCategories from '../hooks/use-course-categories';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn(),
	InspectorControls: ( { children } ) => <>{ children }</>,
	ContrastChecker: () => <h2>Contrast Checker</h2>,
	PanelColorSettings: ( props ) => (
		<>
			<h1>{ props.title } </h1> { props.children }
		</>
	),
	withColors: () => ( Component ) => Component,
} ) );

jest.mock( '../hooks/use-colors' );
jest.mock( '../hooks/use-course-categories' );

const attributes = {
	textAlign: 'left',
};

const context = {
	postId: 'some-post-id',
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
		useColors.mockReturnValue( { textColor: null } );
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
			/>
		);
		categories.forEach( ( category ) =>
			expect( getByText( category.name ) ).toBeInTheDocument()
		);
	} );

	it( 'should contain a custom color settings panel', () => {
		useColors.mockReturnValue( { textColor: null } );
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
			/>
		);
		expect( getByText( 'Category Colors' ) ).toBeInTheDocument();
	} );

	it( 'should contain the contrast checker', () => {
		useColors.mockReturnValue( { textColor: null } );
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
			/>
		);
		expect( getByText( 'Contrast Checker' ) ).toBeInTheDocument();
	} );
} );
