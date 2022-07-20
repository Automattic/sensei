/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';

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
		const confirmResponse = act( () =>
			expect(
				confirm( 'Hey Content', { title: 'Hey Title' } )
			).resolves.toBe( true )
		);
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( true );
		act( () => props.onConfirm() );
		// We need to verify AFTER calling the props.on* callback, otherwise, the promise won't be resolved yet.
		await confirmResponse;
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
	} );

	it( 'confirm should return false when onCancel is called', async () => {
		const { result } = renderHook( () => useConfirmDialogProps() );
		let [ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
		const confirmResponse = act( () =>
			expect(
				confirm( 'Hey Content', { title: 'Hey Title' } )
			).resolves.toBe( false )
		);
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( true );
		act( () => props.onCancel() );
		// We need to verify AFTER calling the props.on* callback, otherwise, the promise won't be resolved yet.
		await confirmResponse;
		[ props, confirm ] = result.current;
		expect( props.isOpen ).toBe( false );
	} );
} );
