/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LimitedTextControl from './index';
import userEvent from '@testing-library/user-event';

describe( '<LimitedTextControl />', () => {
	it( 'Should render the input with the correct value', () => {
		const { queryByRole } = render(
			<LimitedTextControl value="SOME_VALUE" maxLength={ 20 } />
		);

		expect( queryByRole( 'textbox' ) ).toHaveValue( 'SOME_VALUE' );
	} );

	it( 'Should render label', () => {
		const { queryByLabelText } = render(
			<LimitedTextControl
				label="SOME_LABEL"
				value="SOME_VALUE"
				maxLength={ 20 }
			/>
		);

		expect( queryByLabelText( 'SOME_LABEL' ) ).toHaveValue( 'SOME_VALUE' );
	} );

	it( 'Should call the change event', () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value="SOME_VALUE"
				onChange={ onChangeMock }
				maxLength={ 20 }
			/>
		);

		fireEvent.change( queryByRole( 'textbox' ), {
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

	it( 'Should not call the `onChange` method when already at maxLength capacity', async () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value="ABC"
				maxLength={ 3 }
				onChange={ onChangeMock }
			/>
		);

		await userEvent.type( queryByRole( 'textbox' ), 'DEF' );

		expect( onChangeMock ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'Should ignore new lines', async () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value=""
				maxLength={ 20 }
				onChange={ onChangeMock }
			/>
		);

		const element = queryByRole( 'textbox' );

		await userEvent.type( element, '{enter}' );

		expect( onChangeMock ).toHaveBeenCalledTimes( 0 );
	} );
} );

describe( '<LimitedTextControl multiline={ true }/>', () => {
	it( 'Should render the input with the correct value', () => {
		const { queryByRole } = render(
			<LimitedTextControl
				value="SOME_VALUE"
				maxLength={ 20 }
				multiline={ true }
			/>
		);

		expect( queryByRole( 'textbox' ) ).toHaveValue( 'SOME_VALUE' );
	} );

	it( 'Should render label', () => {
		const { queryByLabelText } = render(
			<LimitedTextControl
				label="SOME_LABEL"
				value="SOME_VALUE"
				maxLength={ 20 }
				multiline={ true }
			/>
		);

		expect( queryByLabelText( 'SOME_LABEL' ) ).toHaveValue( 'SOME_VALUE' );
	} );

	it( 'Should call the change event', () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value="SOME_VALUE"
				onChange={ onChangeMock }
				maxLength={ 20 }
				multiline={ true }
			/>
		);

		fireEvent.change( queryByRole( 'textbox' ), {
			target: { value: 'SOME_OTHER_VALUE' },
		} );

		expect( onChangeMock ).toBeCalledWith( 'SOME_OTHER_VALUE' );
	} );

	it( 'Should display the correct character count', () => {
		const { queryByText } = render(
			<LimitedTextControl
				value="SOME_VALUE"
				maxLength={ 20 }
				multiline={ true }
			/>
		);

		expect( queryByText( 'Characters: 10/20' ) ).toBeTruthy();
	} );

	it( 'Should not call the `onChange` method when already at maxLength capacity', async () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value="ABC"
				maxLength={ 3 }
				onChange={ onChangeMock }
				multiline={ true }
			/>
		);

		await userEvent.type( queryByRole( 'textbox' ), 'DEF' );

		expect( onChangeMock ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'Should accept new lines', async () => {
		const onChangeMock = jest.fn();
		const { queryByRole } = render(
			<LimitedTextControl
				value=""
				maxLength={ 20 }
				onChange={ onChangeMock }
				multiline={ true }
			/>
		);

		const element = queryByRole( 'textbox' );

		await userEvent.type( element, '{enter}' );

		expect( onChangeMock ).toHaveBeenCalledTimes( 1 );
	} );
} );
