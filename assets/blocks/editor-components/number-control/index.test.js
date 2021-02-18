/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NumberControl from './index';

describe( '<NumberControl />', () => {
	it( 'Should render the input with the correct value', () => {
		const { queryByDisplayValue } = render(
			<NumberControl value={ 10 } />
		);

		expect( queryByDisplayValue( '10' ) ).toBeTruthy();
	} );

	it( 'Should render with label', () => {
		const { queryByLabelText } = render(
			<NumberControl id="test" label="Test" value={ 10 } />
		);

		expect( queryByLabelText( 'Test' ).value ).toEqual( '10' );
	} );

	it( 'Should render with reset button', () => {
		const { queryByText } = render( <NumberControl allowReset /> );

		expect( queryByText( 'Reset' ) ).toBeTruthy();
	} );

	it( 'Should render with reset button with custom label', () => {
		const { queryByText } = render(
			<NumberControl allowReset resetLabel="Custom reset" />
		);

		expect( queryByText( 'Custom reset' ) ).toBeTruthy();
	} );

	it( 'Should call the change event', () => {
		const onChangeMock = jest.fn();
		const { queryByDisplayValue } = render(
			<NumberControl value={ 10 } onChange={ onChangeMock } />
		);

		fireEvent.change( queryByDisplayValue( '10' ), {
			target: { value: '20' },
		} );

		expect( onChangeMock ).toBeCalledWith( 20 );
	} );

	it( 'Should call the change event with null when resetting', () => {
		const onChangeMock = jest.fn();
		const { queryByText } = render(
			<NumberControl value={ 10 } allowReset onChange={ onChangeMock } />
		);

		fireEvent.click( queryByText( 'Reset' ) );

		expect( onChangeMock ).toBeCalledWith( null );
	} );
} );
