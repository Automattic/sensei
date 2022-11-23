/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import InputControl from './index';

describe( '<InputControl />', () => {
	it( 'Should render the input with the correct value', () => {
		const { queryByDisplayValue } = render( <InputControl value="Hey" /> );

		expect( queryByDisplayValue( 'Hey' ) ).toBeTruthy();
	} );

	it( 'Should render with label', () => {
		const { queryByLabelText } = render(
			<InputControl id="test" label="Test" value="Hey" />
		);

		expect( queryByLabelText( 'Test' ).value ).toEqual( 'Hey' );
	} );

	it( 'Should render with icon right', () => {
		const { container } = render( <InputControl iconRight={ search } /> );

		expect(
			container.querySelectorAll( '.sensei-input-control__icon' )
		).toBeTruthy();
	} );

	it( 'Should call the change event', () => {
		const onChangeMock = jest.fn();
		const { queryByDisplayValue } = render(
			<InputControl value="Hey" onChange={ onChangeMock } />
		);

		fireEvent.change( queryByDisplayValue( 'Hey' ), {
			target: { value: 'Ho!' },
		} );

		expect( onChangeMock ).toBeCalledWith( 'Ho!' );
	} );
} );
