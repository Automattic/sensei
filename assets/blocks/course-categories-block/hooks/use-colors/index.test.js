/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useColors from '.';

jest.mock( '@wordpress/block-editor' );

describe( 'use-colors', () => {
	const attributes = {
		categoryStyle: {
			style: { backgroundColor: '#ffff', color: '#000000' },
		},
	};

	const setters = {
		setCategoryBackgroundColor: jest.fn(),
		setCategoryTextColor: jest.fn(),
		setAttributes: jest.fn(),
	};
	const props = {
		attributes,
		...setters,
		categoryTextColor: null,
		categoryBackgroundColor: null,
	};

	it( 'should load the colors from the attributes only on the first time', () => {
		const propsWithoutColorStyle = {
			...props,
			attributes: { categoryStyle: null },
			defaultCategoryBackgroundColor: {
				color: 'some-default-background-color',
			},
			defaultCategoryTextColor: {
				color: 'some-default-text-color',
			},
		};

		renderHook( () => useColors( propsWithoutColorStyle ) );

		expect( props.setCategoryBackgroundColor ).toHaveBeenCalledWith(
			'some-default-background-color'
		);
		expect( props.setCategoryTextColor ).toHaveBeenCalledWith(
			'some-default-text-color'
		);
	} );

	it( 'should return the default values', () => {
		const { result } = renderHook( () => useColors( props ) );

		expect( result.current ).toEqual( {
			setBackgroundColor: setters.setCategoryBackgroundColor,
			setTextColor: setters.setCategoryTextColor,
			backgroundColor: null,
			textColor: null,
		} );
	} );

	it( 'should update the attributes when the colors are updated', async () => {
		const setAttributes = jest.fn();
		const { rerender } = renderHook( ( hookProps ) =>
			useColors( hookProps )
		);

		rerender( {
			...props,
			setAttributes,
			categoryTextColor: {
				color: 'some-color',
			},
			categoryBackgroundColor: {
				color: 'some-background-color',
			},
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			categoryStyle: {
				classes: [],
				style: {
					backgroundColor: 'some-background-color',
					color: 'some-color',
				},
			},
		} );
	} );

	it( 'should return the colors', () => {
		const propsWithColors = {
			...props,
			categoryTextColor: {
				color: 'some-color',
			},
			categoryBackgroundColor: {
				color: 'some-background-color',
			},
		};
		const { result } = renderHook( () => useColors( propsWithColors ) );

		expect( result.current ).toEqual( {
			setBackgroundColor: setters.setCategoryBackgroundColor,
			setTextColor: setters.setCategoryTextColor,
			textColor: { color: 'some-color' },
			backgroundColor: { color: 'some-background-color' },
		} );
	} );
} );
