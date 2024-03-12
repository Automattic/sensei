/**
 * Internal dependencies
 */
import {
	PerformStepAction,
	highlightElementsWithBorders,
	HIGHLIGHT_CLASS,
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
} );
