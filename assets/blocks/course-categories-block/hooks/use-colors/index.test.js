/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { useSetting } from '@wordpress/block-editor';
import { when } from 'jest-when';
/**
 * Internal dependencies
 */
import useColors from '.';

jest.mock( '@wordpress/block-editor' );

const themeColors = [
	{
		slug: 'foreground',
		color: '#FFFFFF',
		name: 'Foreground',
	},
	{
		slug: 'background',
		color: '#1A1A1A',
		name: 'Background',
	},
	{
		slug: 'primary',
		color: '#FF7179',
		name: 'Primary',
	},
	{
		slug: 'secondary',
		color: '#F4F4F2',
		name: 'Secondary',
	},
	{
		slug: 'tertiary',
		color: '#0000000',
		name: 'Tertiary',
	},
];

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
		};

		when( useSetting )
			.calledWith( 'color.palette.theme' )
			.mockReturnValue( themeColors );

		renderHook( () => useColors( propsWithoutColorStyle ) );

		expect( props.setCategoryBackgroundColor ).toHaveBeenCalledWith(
			themeColors[ 1 ].color // foreground
		);
		expect( props.setCategoryTextColor ).toHaveBeenCalledWith(
			themeColors[ 2 ].color // background
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
