/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PurposeItem from './purpose-item';

describe( '<PurposeItem />', () => {
	it( 'Should display children when checked', () => {
		const { queryByText } = render(
			<PurposeItem checked={ true }>Content</PurposeItem>
		);

		expect( queryByText( 'Content' ) ).toBeTruthy();
	} );

	it( 'Should not display children when not checked', () => {
		const { queryByText } = render(
			<PurposeItem checked={ false }>Content</PurposeItem>
		);

		expect( queryByText( 'Content' ) ).toBeFalsy();
	} );

	it( 'Should trigger onToggle when checked', () => {
		const onToggleMock = jest.fn();

		const { queryByLabelText } = render(
			<PurposeItem
				label="Label"
				checked={ false }
				onToggle={ onToggleMock }
			>
				Content
			</PurposeItem>
		);

		fireEvent.click( queryByLabelText( 'Label' ) );
		expect( onToggleMock ).toBeCalled();
	} );
} );
