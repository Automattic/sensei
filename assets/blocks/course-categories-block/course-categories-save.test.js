/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import CourseCategorySave from './course-categories-save';
import useCourseCategoriesProps from './hooks/use-course-categories-props';

const attributes = {
	textAlign: 'left',
	options: {},
};

const defaultProps = {
	attributes,
};

jest.mock( './hooks/use-course-categories-props' );

describe( 'CourseCategorySave', () => {
	it( 'should render classes from the hook', () => {
		useCourseCategoriesProps.mockReturnValue( {
			style: { color: 'some-color' },
			className: 'has-class',
		} );

		const { container } = render(
			<CourseCategorySave { ...defaultProps } />
		);

		expect(
			container.firstChild.classList.contains( 'has-class' )
		).toEqual( true );
	} );

	it( 'should render styles from the hook', () => {
		useCourseCategoriesProps.mockReturnValue( {
			style: { backgroundColor: 'red' },
			className: 'has-class',
		} );

		const { container } = render(
			<CourseCategorySave { ...defaultProps } />
		);

		expect( container.firstChild ).toHaveAttribute(
			'style',
			'background-color: red;'
		);
	} );
} );
