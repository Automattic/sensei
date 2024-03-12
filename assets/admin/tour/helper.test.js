/**
 * Internal dependencies
 */
import {
	PerformStepAction,
	highlightElementsWithBorders,
	HIGHLIGHT_CLASS,
	removeHighlightClasses,
} from './helper';

describe( 'PerformStepAction', () => {
	it( 'should execute action if it exists for the given index', () => {
		const steps = [
			{ action: jest.fn() },
			{ action: jest.fn() },
			{ action: jest.fn() },
		];

		PerformStepAction( 0, steps );

		expect( steps[ 0 ].action ).toHaveBeenCalled();
	} );

	it( 'should not execute action if index is greater than or equal to steps length', () => {
		const steps = [ { action: jest.fn() }, { action: jest.fn() } ];

		PerformStepAction( 2, steps );

		expect( steps[ 0 ].action ).not.toHaveBeenCalled();
		expect( steps[ 1 ].action ).not.toHaveBeenCalled();
	} );
} );

describe( 'highlightElementsWithBorders', () => {
	const mockQuerySelector = jest.spyOn( document, 'querySelector' );

	beforeEach( () => {
		mockQuerySelector.mockClear();
	} );

	it( 'should add highlight class to elements that exist', () => {
		const element1 = document.createElement( 'div' );
		const element2 = document.createElement( 'div' );

		mockQuerySelector.mockImplementation( ( selector ) => {
			if ( '.selector1' === selector ) {
				return element1;
			} else if ( '.selector2' === selector ) {
				return element2;
			}
			return null;
		} );

		highlightElementsWithBorders( [ '.selector1' ] );

		expect( mockQuerySelector ).toHaveBeenCalledTimes( 1 );

		expect( element1.classList.contains( HIGHLIGHT_CLASS ) ).toBe( true );
		expect( element2.classList.contains( HIGHLIGHT_CLASS ) ).toBe( false );

		highlightElementsWithBorders( [ '.selector2' ] );
		expect( mockQuerySelector ).toHaveBeenCalledTimes( 2 );
		expect( element2.classList.contains( HIGHLIGHT_CLASS ) ).toBe( true );
	} );

	it( 'should not add highlight class to elements that do not exist', () => {
		const selectors = [ '.selector3', '.selector4' ];

		mockQuerySelector.mockImplementation( ( selector ) => {
			if ( selectors.includes( selector ) ) {
				return null;
			}
			return document.createElement( 'div' );
		} );

		highlightElementsWithBorders( selectors );

		expect( mockQuerySelector ).toHaveBeenCalledTimes( selectors.length );

		selectors.forEach( ( selector ) => {
			expect( document.querySelector( selector ) ).toBeNull();
		} );
	} );
} );

describe( 'removeHighlightClasses', () => {
	const mockQuerySelectorAll = jest.spyOn( document, 'querySelectorAll' );

	beforeEach( () => {
		mockQuerySelectorAll.mockClear();
	} );

	it( 'should remove highlight class from elements with .sensei-tour-highlight class', () => {
		const mockedElements = [
			document.createElement( 'div' ),
			document.createElement( 'div' ),
		];

		mockedElements.forEach( ( element ) => {
			element.classList.add( 'sensei-tour-highlight' );
		} );

		mockQuerySelectorAll.mockReturnValue( mockedElements );

		removeHighlightClasses();

		expect( mockQuerySelectorAll ).toHaveBeenCalledWith(
			'.sensei-tour-highlight'
		);

		mockedElements.forEach( ( element ) => {
			expect( element.classList.contains( HIGHLIGHT_CLASS ) ).toBe(
				false
			);
		} );
	} );

	it( 'should not remove highlight class from elements without .sensei-tour-highlight class', () => {
		const mockedElements = [
			document.createElement( 'div' ),
			document.createElement( 'div' ),
		];

		mockQuerySelectorAll.mockReturnValue( mockedElements );

		removeHighlightClasses();

		expect( mockQuerySelectorAll ).toHaveBeenCalledWith(
			'.sensei-tour-highlight'
		);

		mockedElements.forEach( ( element ) => {
			expect( element.classList.contains( HIGHLIGHT_CLASS ) ).toBe(
				false
			);
		} );
	} );
} );
