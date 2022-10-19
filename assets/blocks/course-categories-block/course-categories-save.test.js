/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import CourseCategorySave from './course-categories-save';

const attributes = {
	textAlign: 'left',
	options: {},
};

const defaultProps = {
	attributes,
};

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: { save: jest.fn( ( params ) => params ) },
} ) );

describe( 'CourseCategorySave', () => {
	it( 'should render classes from the hook', () => {
		const props = {
			...defaultProps,
			attributes: {
				textAlign: 'left',
			},
		};

		const { container } = render( <CourseCategorySave { ...props } /> );

		expect(
			container.firstChild.classList.contains( 'has-text-align-left' )
		).toEqual( true );
	} );

	it( 'should render styles from the attributes', () => {
		const props = {
			...defaultProps,
			attributes: {
				options: {
					backgroundColor: 'red',
				},
			},
		};
		const { container } = render( <CourseCategorySave { ...props } /> );

		expect( container.firstChild ).toHaveAttribute(
			'style',
			'--sensei-lms-course-categories-background-color: red;'
		);
	} );
} );
