/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAddExistingLessons } from './use-add-existing-lessons';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/api-fetch' );
jest.mock( '@wordpress/blocks' );

describe( 'useAddExistingLessons', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( [] );
		useDispatch.mockReturnValue( {
			insertBlock: () => {},
		} );
	} );

	it( 'Should return a function', () => {
		const addExistingLessons = useAddExistingLessons( 1 );

		expect( typeof addExistingLessons ).toBe( 'function' );
	} );

	it( 'Should return a function that returns a Promise', () => {
		const addExistingLessons = useAddExistingLessons( 1 );

		expect( typeof addExistingLessons( [] ).then ).toBe( 'function' );
	} );

	it( 'Should create a block for each lesson', async () => {
		const addExistingLessons = useAddExistingLessons( 1 );

		apiFetch.mockResolvedValue( [
			{
				id: 1,
				title: {
					raw: 'Lesson 1',
				},
				course: {
					title: {
						raw: 'Course 1',
					},
				},
				meta: {
					_lesson_course: 1,
				},
			},
			{
				id: 2,
				title: {
					raw: 'Lesson 2',
				},
				course: {
					title: {
						raw: 'Course 2',
					},
				},
				meta: {
					_lesson_course: 2,
				},
			},
		] );

		await addExistingLessons( [ 1, 2 ] );

		expect( createBlock ).toHaveBeenCalledTimes( 2 );
	} );
} );
