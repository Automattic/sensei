/**
 * External dependencies
 */
import { render, renderHook } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useCommand, useCommandLoader } from '@wordpress/commands';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CommandPalette from './command-palette';

jest.mock( '@wordpress/commands' );
jest.mock( '@wordpress/data' );

describe( '<CommandPalette />', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( selector ) =>
			selector( () => ( {
				canUser: () => true,
				getEntityRecords: () => [],
				hasFinishedResolution: () => true,
			} ) )
		);
	} );

	it( 'registers an "Add new" command for Course, Lesson and Question', () => {
		render( <CommandPalette /> );

		const names = useCommand.mock.calls.map( ( [ args ] ) => args.name );

		expect( names ).toEqual( [
			'sensei-lms/add-new-course',
			'sensei-lms/add-new-lesson',
			'sensei-lms/add-new-question',
		] );
		expect( useCommand.mock.calls[ 0 ][ 0 ].label ).toBe(
			'Add new Course'
		);
	} );

	it( 'does not register an "Add new" command for a post type the user cannot create', () => {
		useSelect.mockImplementation( ( selector ) =>
			selector( () => ( {
				canUser: ( action, { name } ) =>
					! ( action === 'create' && name === 'course' ),
				getEntityRecords: () => [],
				hasFinishedResolution: () => true,
			} ) )
		);

		render( <CommandPalette /> );

		const names = useCommand.mock.calls.map( ( [ args ] ) => args.name );

		expect( names ).toEqual( [
			'sensei-lms/add-new-lesson',
			'sensei-lms/add-new-question',
		] );
	} );

	it( 'registers a search command loader for Course, Lesson and Question', () => {
		render( <CommandPalette /> );

		const names = useCommandLoader.mock.calls.map(
			( [ args ] ) => args.name
		);

		expect( names ).toEqual( [
			'sensei-lms/search-course',
			'sensei-lms/search-lesson',
			'sensei-lms/search-question',
		] );
	} );

	it( 'maps found posts to labeled, navigable commands', () => {
		render( <CommandPalette /> );

		const courseLoader = useCommandLoader.mock.calls.find(
			( [ args ] ) => args.name === 'sensei-lms/search-course'
		)[ 0 ].hook;

		useSelect.mockImplementation( ( selector ) =>
			selector( () => ( {
				getEntityRecords: () => [
					{ id: 12, title: { rendered: 'Intro to Testing' } },
				],
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () =>
			courseLoader( { search: 'testing' } )
		);

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.commands ).toHaveLength( 1 );
		expect( result.current.commands[ 0 ] ).toMatchObject( {
			name: 'sensei-lms/course-12',
			label: 'Course: Intro to Testing',
		} );
	} );
} );
