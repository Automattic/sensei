import { render } from '@testing-library/react';
import useThemeColors from './use-theme-colors';

describe( 'useThemeColors', () => {
	it( 'Should get the theme colors', () => {
		const TestComponent = () => {
			const themeColors = useThemeColors();
			return (
				<>
					<style>
						{ `.wp-block-button__link {
							background-color: rgb(0, 0, 0);
							color: rgb(255, 255, 255);
						}` }
					</style>
					<div
						data-testid="styled-element"
						style={ {
							backgroundColor: themeColors.primaryColor,
							color: themeColors.primaryContrastColor,
						} }
					/>
				</>
			);
		};

		const { getByTestId } = render( <TestComponent /> );

		expect( getByTestId( 'styled-element' ).style.backgroundColor ).toEqual(
			'rgb(0, 0, 0)'
		);
		expect( getByTestId( 'styled-element' ).style.color ).toEqual(
			'rgb(255, 255, 255)'
		);
	} );
} );
