/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
/**
 * Internal dependencies
 */
import useCourseCategoriesProps from '.';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: ( attributes ) => ( { ...attributes } ),
} ) );

describe( 'useCourseCategoriesProps', () => {
	it( 'should return the default css classes and styles', () => {
		const { result } = renderHook( () => useCourseCategoriesProps() );

		expect( result.current ).toEqual( {
			className: 'taxonomy-course-category',
			style: {},
		} );
	} );

	it( 'should return css colors when colors are set', () => {
		const { result } = renderHook( () =>
			useCourseCategoriesProps( {
				options: {
					backgroundColor: 'some-background-color',
					textColor: 'some-text-color',
				},
			} )
		);

		expect( result.current ).toEqual( {
			className: 'taxonomy-course-category',
			style: {
				'--sensei-lms-course-categories-background-color':
					'some-background-color',
				'--sensei-lms-course-categories-text-color': 'some-text-color',
			},
		} );
	} );

	it( 'should return the text-align class', () => {
		const { result } = renderHook( () =>
			useCourseCategoriesProps( {
				textAlign: 'left',
			} )
		);

		expect( result.current.className ).toEqual(
			'has-text-align-left taxonomy-course-category'
		);
	} );
} );
