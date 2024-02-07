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
	const lessons = [
		{
			id: 1,
			title: {
				raw: 'Lesson 1',
			},
			meta: {
				_lesson_course: 0,
			},
		},
		{
			id: 2,
			title: {
				raw: 'Lesson 2',
			},
			meta: {
				_lesson_course: 0,
			},
		},
		{
			id: 3,
			title: {
				raw: 'Lesson 3',
			},
			meta: {
				_lesson_course: 0,
			},
		},
	];

	it( 'Should have Add Selected button when nothing selected', () => {
		const { getByText } = render(
			<Actions
				selectedLessons={ [] }
				setSelectedLessons={ () => {} }
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
				selectedLessons={ lessons }
				setSelectedLessons={ () => {} }
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
				selectedLessons={ lessons }
				setSelectedLessons={ () => {} }
				onAdd={ onAdd }
				closeModal={ () => {} }
				setErrorAddingSelected={ () => {} }
			/>
		);

		fireEvent.click( getByText( 'Add Selected (3)' ) );

		expect( onAdd ).toHaveBeenCalled();
	} );
} );
