/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LimitedTextControl from './index';

describe( '<LimitedTextControl />', () => {
	it( 'Should render the input with the correct value', () => {
		const { queryByDisplayValue } = render(
			<LimitedTextControl value="SOME_VALUE" maxLength={ 20 } />
		);

		expect( queryByDisplayValue( 'SOME_VALUE' ) ).toBeTruthy();
	} );

	it( 'Should render with label', () => {
		const { queryByLabelText } = render(
			<LimitedTextControl
				label="SOME_LABEL"
				value="SOME_VALUE"
				maxLength={ 20 }
			/>
		);

		expect( queryByLabelText( 'SOME_LABEL' ).value ).toEqual(
			'SOME_VALUE'
		);
	} );

	it( 'Should call the change event', () => {
		const onChangeMock = jest.fn();
		const { queryByDisplayValue } = render(
			<LimitedTextControl
				value="SOME_VALUE"
				onChange={ onChangeMock }
				maxLength={ 20 }
			/>
		);

		fireEvent.change( queryByDisplayValue( 'SOME_VALUE' ), {
			target: { value: 'SOME_OTHER_VALUE' },
		} );

		expect( onChangeMock ).toBeCalledWith( 'SOME_OTHER_VALUE' );
	} );

	it( 'Should display the correct character count', () => {
		const { queryByText } = render(
			<LimitedTextControl value="SOME_VALUE" maxLength={ 20 } />
		);

		expect( queryByText( 'Characters: 10/20' ) ).toBeTruthy();
	} );
} );
