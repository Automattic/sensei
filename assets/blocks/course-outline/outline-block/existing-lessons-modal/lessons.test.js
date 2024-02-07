/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Lessons from './lessons';

jest.mock( '@wordpress/data' );
jest.mock( './use-add-existing-lessons' );

describe( '<Lessons />', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( [] );
	} );
	it( 'Should have column headers', () => {
		const { getByText } = render(
			<Lessons
				clientId={ 1 }
				filters={ [] }
				selectedLessons={ [] }
				setSelectedLessons={ () => {} }
			/>
		);

		expect( getByText( 'Lesson' ) ).toBeVisible();
	} );

	it( 'Should render lessons', () => {
		useSelect.mockReturnValueOnce( [] ).mockReturnValueOnce( [
			{
				id: 1,
				title: {
					raw: 'Lesson 1',
				},
				meta: {
					_lesson_course: 0,
				},
			},
		] );
		const { getByText } = render(
			<Lessons
				clientId={ 1 }
				filters={ [] }
				selectedLessons={ [] }
				setSelectedLessons={ () => {} }
			/>
		);

		expect( getByText( 'Lesson 1' ) ).toBeVisible();
	} );
} );
