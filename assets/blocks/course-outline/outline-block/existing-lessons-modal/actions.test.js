/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import Actions from './actions';

describe( '<Actions />', () => {
	it( 'Should have Add Selected button when nothing selected', () => {
		const { getByText } = render(
			<Actions
				selectedLessonIds={ [] }
				setSelectedLessonIds={ () => {} }
				onAdd={ () => {} }
				closeModal={ () => {} }
				setErrorAddingSelected={ () => {} }
			/>
		);

		expect( getByText( /^Add Selected$/ ) ).toBeVisible();
	} );

	it( 'Should have Add Selected button with a number of selected lessons', () => {
		const { getByText } = render(
			<Actions
				selectedLessonIds={ [ 1, 2, 3 ] }
				setSelectedLessonIds={ () => {} }
				onAdd={ () => {} }
				closeModal={ () => {} }
				setErrorAddingSelected={ () => {} }
			/>
		);

		expect( getByText( 'Add Selected (3)' ) ).toBeVisible();
	} );

	it( 'Should call onAdd when Add Selected button clicked', () => {
		const onAdd = jest.fn().mockResolvedValue( true );

		const { getByText } = render(
			<Actions
				selectedLessonIds={ [ 1, 2, 3 ] }
				setSelectedLessonIds={ () => {} }
				onAdd={ onAdd }
				closeModal={ () => {} }
				setErrorAddingSelected={ () => {} }
			/>
		);

		fireEvent.click( getByText( 'Add Selected (3)' ) );

		expect( onAdd ).toHaveBeenCalled();
	} );
} );
