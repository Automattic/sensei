/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useConfirmDialogProps from './use-confirm-dialog-props';

describe( 'useConfirmDialogProps()', () => {
	it( 'Should return isOpen as false by default', () => {
		const { result } = renderHook( () => useConfirmDialogProps() );
		const [ props ] = result.current;
		expect( props.isOpen ).toBe( false );
	} );

	it( 'Should set Confirm Dialog props when calling confirm', () => {
		const { result } = renderHook( () => useConfirmDialogProps() );
		let [ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
		act( () => {
			confirm( 'Hey Content', { title: 'Hey Title' } );
		} );
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( true );
		expect( props.title ).toBe( 'Hey Title' );
		expect( props.children ).toBe( 'Hey Content' );
		expect( props.onConfirm ).toBeInstanceOf( Function );
		expect( props.onCancel ).toBeInstanceOf( Function );
	} );

	it( 'confirm should return true when onConfirm is called', async () => {
		const { result } = renderHook( () => useConfirmDialogProps() );
		let [ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
		act( () => {
			// eslint-disable-next-line jest/valid-expect
			expect(
				confirm( 'Hey Content', { title: 'Hey Title' } )
			).resolves.toBe( true );
		} );
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( true );
		act( () => props.onConfirm() );
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
	} );

	it( 'confirm should return false when onCancel is called', async () => {
		const { result } = renderHook( () => useConfirmDialogProps() );
		let [ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
		act( () => {
			// eslint-disable-next-line jest/valid-expect
			expect(
				confirm( 'Hey Content', { title: 'Hey Title' } )
			).resolves.toBe( false );
		} );
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( true );
		act( () => props.onCancel() );
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
	} );
} );
