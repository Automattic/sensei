/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SingleLineInput from './index';

describe( '<SingleLineInput />', () => {
	it( 'Should render the single line input correctly', () => {
		const { getByRole } = render(
			<SingleLineInput
				className="custom-class"
				placeholder="extra props"
			/>
		);

		const input = getByRole( 'textbox' );

		expect( input ).toBeTruthy();
		expect( input.classList.contains( 'custom-class' ) ).toBeTruthy();
		expect( input.getAttribute( 'placeholder' ) ).toEqual( 'extra props' );
	} );

	it( 'Should call the onChange', () => {
		const onChangeMock = jest.fn();
		const { getByRole } = render(
			<SingleLineInput onChange={ onChangeMock } />
		);

		fireEvent.change( getByRole( 'textbox' ), {
			target: { value: 'changed' },
		} );

		expect( onChangeMock ).toBeCalledWith( 'changed' );
	} );

	it( 'Should not allow line breaks', () => {
		const onChangeMock = jest.fn();
		const { getByRole } = render(
			<SingleLineInput onChange={ onChangeMock } />
		);

		userEvent.type( getByRole( 'textbox' ), 'input {enter}line' );

		expect( onChangeMock ).toHaveBeenLastCalledWith( 'input line' );
	} );

	it( 'Calls onRemove on backspace with an empty title', () => {
		const onRemoveMock = jest.fn();
		const { getByRole } = render(
			<SingleLineInput
				onRemove={ onRemoveMock }
				value=""
				onChange={ () => {} }
			/>
		);

		userEvent.type( getByRole( 'textbox' ), '{backspace}' );

		expect( onRemoveMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Calls onEnter on enter', () => {
		const onEnterMock = jest.fn();
		const { getByRole } = render(
			<SingleLineInput onEnter={ onEnterMock } onChange={ jest.fn() } />
		);

		userEvent.type( getByRole( 'textbox' ), 'Title{enter}' );

		expect( onEnterMock ).toHaveBeenCalledTimes( 1 );
	} );
} );
