/**
 * Internal dependencies
 */
import { getStyleAndClassesFromAttributes } from './style';

describe( 'useCourseCategoriesProps', () => {
	it( 'should return the default css classes and styles', () => {
		const result = getStyleAndClassesFromAttributes();

		expect( result ).toEqual( { className: '', style: {} } );
	} );

	it( 'should return css colors when colors are set', () => {
		const result = getStyleAndClassesFromAttributes( {
			options: {
				backgroundColor: 'some-background-color',
				textColor: 'some-text-color',
			},
		} );

		expect( result ).toEqual( {
			className: '',
			style: {
				'--sensei-lms-course-categories-background-color':
					'some-background-color',
				'--sensei-lms-course-categories-text-color': 'some-text-color',
			},
		} );
	} );

	it( 'should return the text-align class', () => {
		const result = getStyleAndClassesFromAttributes( {
			textAlign: 'left',
		} );

		expect( result.className ).toEqual( 'has-text-align-left' );
	} );
} );
