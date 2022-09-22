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
	BlockControls: () => null,
	AlignmentToolbar: () => null,
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

const defaultProps = {
	clientId: 'some-client-id',
	attributes,
	context,
	setAttributes: jest.fn(),
	setBackgroundColor: jest.fn(),
	setTextColor: jest.fn(),
};

jest.mock( './hooks/use-course-category-props', () => () => ( {
	style: {},
	classNames: [],
} ) );

describe( 'CourseCategoryEdit', () => {
	it( 'should render the categories', () => {
		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: true,
			postTerms: categories,
		} );

		const { getByText } = render(
			<CourseCategoryEdit { ...defaultProps } />
		);
		categories.forEach( ( category ) =>
			expect( getByText( category.name ) ).toBeInTheDocument()
		);
	} );

	it( 'should render an error when the block is not using the correct post type', () => {
		const { getByText } = render(
			<CourseCategoryEdit
				{ ...defaultProps }
				context={ {
					postId: 'some-post-id',
					postType: 'page',
				} }
			/>
		);

		expect( getByText( message ) ).toBeInTheDocument();
	} );

	it( 'should render a placeholder when there are no course categories', () => {
		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: false,
			postTerms: [],
		} );

		const { getByText } = render(
			<CourseCategoryEdit { ...defaultProps } />
		);

		expect( getByText( 'No course category' ) ).toBeInTheDocument();
	} );

	it( 'should update the color attributes during the component loading', () => {
		const setBackgroundColor = jest.fn();
		const setTextColor = jest.fn();

		const attributesWithOptions = {
			...attributes,
			options: {
				backgroundColor: 'some-background-color',
				textColor: 'some-text-color',
			},
		};

		useCourseCategories.mockReturnValue( {
			isLoading: false,
			hasPostTerms: true,
			postTerms: categories,
		} );

		render(
			<CourseCategoryEdit
				{ ...defaultProps }
				attributes={ attributesWithOptions }
				setBackgroundColor={ setBackgroundColor }
				setTextColor={ setTextColor }
			/>
		);
		expect( setBackgroundColor ).toHaveBeenCalledWith(
			'some-background-color'
		);

		expect( setTextColor ).toHaveBeenCalledWith( 'some-text-color' );
	} );
} );
