/**
 * Internal dependencies
 */
import {
	performStepAction,
	highlightElementsWithBorders,
	HIGHLIGHT_CLASS,
	removeHighlightClasses,
	performStepActionsAsync,
	waitForElement,
} from './helper';

describe( 'performStepAction', () => {
	it( 'should execute action if it exists for the given index', () => {
		const steps = [
			{ action: jest.fn() },
			{ action: jest.fn() },
			{ action: jest.fn() },
		];

		performStepAction( 0, steps );

		expect( steps[ 0 ].action ).toHaveBeenCalled();
	} );

	it( 'should not execute action if index is greater than or equal to steps length', () => {
		const steps = [ { action: jest.fn() }, { action: jest.fn() } ];

		performStepAction( 2, steps );

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

	it( 'should add highlight class to the element with a modifier', () => {
		const element = document.createElement( 'div' );

		mockQuerySelector.mockImplementation( () => {
			return element;
		} );

		highlightElementsWithBorders( [ 'div' ], 'modifier' );

		expect( element.className ).toBe(
			`${ HIGHLIGHT_CLASS } ${ HIGHLIGHT_CLASS }--modifier`
		);
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

	it( 'should add remove modifier classNames', () => {
		const mockedElement = document.createElement( 'div' );

		mockedElement.classList.add(
			'any-other-class',
			HIGHLIGHT_CLASS,
			HIGHLIGHT_CLASS + '--modifier'
		);

		mockQuerySelectorAll.mockReturnValue( [ mockedElement ] );

		removeHighlightClasses();

		expect( mockedElement.classList.contains( HIGHLIGHT_CLASS ) ).toBe(
			false
		);
		expect(
			mockedElement.classList.contains( HIGHLIGHT_CLASS + '--modifier' )
		).toBe( false );
		expect( mockedElement.classList.contains( 'any-other-class' ) ).toBe(
			true
		);
	} );
} );

describe( 'performStepActionsAsync', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		jest.useRealTimers();
	} );

	it( 'should perform step actions one after another with specified delays', async () => {
		jest.useFakeTimers();
		jest.spyOn( global, 'setTimeout' );
		const stepActions = [
			{ action: jest.fn(), delay: 100 },
			{ action: jest.fn(), delay: 200 },
			{ action: jest.fn() },
		];

		performStepActionsAsync( stepActions );

		await jest.runAllTimers();
		expect( stepActions[ 0 ].action ).toHaveBeenCalledTimes( 1 );
		await jest.runAllTimers();
		expect( stepActions[ 1 ].action ).toHaveBeenCalledTimes( 1 );
		await jest.runAllTimers();
		expect( stepActions[ 2 ].action ).toHaveBeenCalledTimes( 1 );

		expect( setTimeout ).toHaveBeenNthCalledWith(
			1,
			expect.any( Function ),
			100
		);
		expect( setTimeout ).toHaveBeenNthCalledWith(
			2,
			expect.any( Function ),
			200
		);
		expect( setTimeout ).toHaveBeenNthCalledWith(
			3,
			expect.any( Function ),
			0
		);
	} );

	it( 'should use setTimeout when delay is not specified', async () => {
		jest.spyOn( global, 'setTimeout' );
		const stepActions = [
			{ action: jest.fn() },
			{ action: jest.fn() },
			{ action: jest.fn() },
		];

		await performStepActionsAsync( stepActions );

		expect( stepActions[ 0 ].action ).toHaveBeenCalledTimes( 1 );
		expect( stepActions[ 1 ].action ).toHaveBeenCalledTimes( 1 );
		expect( stepActions[ 2 ].action ).toHaveBeenCalledTimes( 1 );
		expect( setTimeout ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'should stop previous step actions if starting a new one', async () => {
		jest.useFakeTimers();
		jest.spyOn( global, 'setTimeout' );
		const stepActions = [
			{ action: jest.fn(), delay: 100 },
			{ action: jest.fn(), delay: 200 },
		];

		performStepActionsAsync( stepActions );

		await jest.runAllTimers();
		expect( stepActions[ 0 ].action ).toHaveBeenCalledTimes( 1 );
		performStepActionsAsync( [] );
		await jest.runAllTimers();
		expect( stepActions[ 1 ].action ).not.toHaveBeenCalled();
	} );
} );

describe( 'waitForElement', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'should resolve the promise when the element is available within maxChecks', async () => {
		const selector = '.test-element';
		const element = document.createElement( 'div' );
		jest.spyOn( document, 'querySelector' ).mockReturnValueOnce( element );

		const promise = waitForElement( selector );

		jest.runAllTimers();

		await expect( promise ).resolves.toBe( element );
	} );

	it( 'should reject the promise when the element is not available within maxChecks', async () => {
		const selector = '.test-element';
		jest.spyOn( document, 'querySelector' ).mockReturnValueOnce( null );

		const promise = waitForElement( selector, 3, 100 );

		jest.advanceTimersByTime( 1500 );

		await expect( promise ).rejects.toBe( undefined );
	} );
} );
