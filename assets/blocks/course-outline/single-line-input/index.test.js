import { render, fireEvent } from '@testing-library/react';

import SingleLineInput from './index';

describe( '<SingleLineInput />', () => {
	it( 'Should render the single line input correctly', () => {
		const { container } = render(
			<SingleLineInput
				className="custom-class"
				placeholder="extra props"
			/>
		);

		const input = container.querySelector( 'input' );

		expect( input ).toBeTruthy();
		expect( input.classList.contains( 'custom-class' ) ).toBeTruthy();
		expect(
			input.classList.contains(
				'wp-block-sensei-lms-course-outline__clean-input'
			)
		).toBeTruthy();
		expect( input.getAttribute( 'placeholder' ) ).toEqual( 'extra props' );
	} );

	it( 'Should call the onChange', () => {
		const onChangeMock = jest.fn();
		const { container } = render(
			<SingleLineInput onChange={ onChangeMock } />
		);

		fireEvent.change( container.firstChild, {
			target: { value: 'changed' },
		} );

		expect( onChangeMock ).toBeCalledWith( 'changed' );
	} );
} );
