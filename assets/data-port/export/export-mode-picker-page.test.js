/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ExportModePickerPage } from './export-mode-picker-page';

describe( '<ExportModePickerPage />', () => {
	it( 'invokes onPickMode with by_course when the first card is clicked', () => {
		const onPickMode = jest.fn();
		const { getByRole } = render(
			<ExportModePickerPage onPickMode={ onPickMode } />
		);

		fireEvent.click( getByRole( 'button', { name: /By course/ } ) );

		expect( onPickMode ).toHaveBeenCalledWith( 'by_course' );
	} );

	it( 'invokes onPickMode with by_file_type when the second card is clicked', () => {
		const onPickMode = jest.fn();
		const { getByRole } = render(
			<ExportModePickerPage onPickMode={ onPickMode } />
		);

		fireEvent.click( getByRole( 'button', { name: /By file type/ } ) );

		expect( onPickMode ).toHaveBeenCalledWith( 'by_file_type' );
	} );
} );
