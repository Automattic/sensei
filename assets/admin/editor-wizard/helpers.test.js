/**
 * External dependencies
 */
import { render, renderHook, fireEvent, act } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { parse } from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import {
	getSenseiPatterns,
	replacePlaceholders,
	useSetDefaultPattern,
	useWizardOpenState,
} from './helpers';

jest.mock( '@wordpress/data', () =>
	require( '../../../tests/mocks/wordpress-data' )( {
		useDispatch: jest.fn(),
		useSelect: jest.fn(),
	} )
);
jest.mock( '@wordpress/blocks', () => ( {
	...jest.requireActual( '@wordpress/blocks' ),
	parse: jest.fn(),
} ) );

describe( 'replacePlaceholders', () => {
	const replaces = {
		title: 'New title',
		description: 'New description',
	};

	it( 'Should replace the placeholder content properly', () => {
		const blocks = [
			{
				attributes: {
					className: 'title',
					content: 'Title placeholder',
				},
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'Description placeholder',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const expectedBlocks = [
			{
				attributes: { className: 'title', content: 'New title' },
			},
			{
				attributes: {},
				innerBlocks: [
					{
						attributes: {
							className: 'description',
							content: 'New description',
						},
					},
					{
						attributes: {
							className: 'unrelated',
							content: 'Unrelated content',
						},
					},
					{
						attributes: { content: 'Another unrelated content' },
					},
				],
			},
		];

		const newBlocks = replacePlaceholders( blocks, replaces );

		expect( newBlocks ).toEqual( expectedBlocks );
	} );
} );

describe( 'getSenseiPatterns', () => {
	beforeEach( () => {
		parse.mockReset();
	} );

	it( 'Should filter Sensei patterns and parse serialized content', () => {
		const parsedBlocks = [
			{
				name: 'core/paragraph',
				attributes: { content: 'Course content' },
			},
		];
		parse.mockReturnValue( [
			{
				name: 'core/paragraph',
				attributes: { content: 'Course content' },
			},
		] );
		const patterns = getSenseiPatterns( [
			{
				name: 'sensei-lms/course-default',
				blockTypes: [ 'sensei-lms/post-content' ],
				content: '<!-- wp:paragraph {"content":"Course content"} /-->',
			},
			{
				name: 'core/query-standard-posts',
				blockTypes: [ 'core/query' ],
				content: '<!-- wp:query /-->',
			},
		] );

		expect( patterns ).toHaveLength( 1 );
		expect( patterns[ 0 ].name ).toBe( 'sensei-lms/course-default' );
		expect( patterns[ 0 ].blocks ).toEqual( parsedBlocks );
		expect( parse ).toHaveBeenCalledWith(
			'<!-- wp:paragraph {"content":"Course content"} /-->',
			{ __unstableSkipMigrationLogs: true }
		);
	} );

	it( 'Should prefer the first version of a duplicate pattern', () => {
		const editorBlocks = [ { name: 'core/paragraph' } ];
		const patterns = getSenseiPatterns( [
			{
				name: 'sensei-lms/course-default',
				blockTypes: [ 'sensei-lms/post-content' ],
				blocks: editorBlocks,
			},
			{
				name: 'sensei-lms/course-default',
				blockTypes: [ 'sensei-lms/post-content' ],
				content: '<!-- wp:paragraph /-->',
			},
		] );

		expect( patterns ).toHaveLength( 1 );
		expect( patterns[ 0 ].blocks ).toBe( editorBlocks );
		expect( parse ).not.toHaveBeenCalled();
	} );
} );

describe( 'useSetDefaultPattern', () => {
	it( 'Should use the post type template when setting the default pattern', () => {
		const blocks = [ { name: 'core/paragraph', attributes: {} } ];
		parse.mockReturnValue( blocks );
		const resetEditorBlocks = jest.fn();
		const getBlockPatterns = jest.fn( () => [
			{
				name: 'sensei-lms/course-default',
				blockTypes: [ 'sensei-lms/post-content' ],
				content: '<!-- wp:paragraph /-->',
			},
		] );
		const getCurrentPostType = jest.fn( () => 'course' );
		const getEditorSettings = jest.fn( () => ( {} ) );
		const getPostType = jest.fn( () => ( {
			template: [
				[ 'core/pattern', { slug: 'sensei-lms/course-default' } ],
			],
		} ) );
		useSelect.mockImplementation( ( callback ) =>
			callback( ( store ) => {
				if ( coreStore === store ) {
					return { getBlockPatterns, getPostType };
				}

				if ( editorStore === store ) {
					return { getCurrentPostType, getEditorSettings };
				}
			} )
		);
		useDispatch.mockReturnValue( { resetEditorBlocks } );
		const { result } = renderHook( () => useSetDefaultPattern( {} ) );

		result.current();

		expect( resetEditorBlocks ).toHaveBeenCalledWith( [
			expect.objectContaining( { name: 'core/paragraph' } ),
		] );
	} );
} );

describe( 'useWizardOpenState', () => {
	const TestComponent = () => {
		const [ open, setDone ] = useWizardOpenState();

		return (
			<div>
				{ open ? 'open' : 'closed' }
				<button onClick={ () => setDone( true ) }>done</button>
			</div>
		);
	};

	beforeAll( () => {
		jest.useFakeTimers();
	} );

	it( 'Should start open when no other modal is open', () => {
		const { queryByText } = render( <TestComponent /> );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( queryByText( 'open' ) ).toBeTruthy();
	} );

	it( 'Should open when other modals get closed', async () => {
		document.body.classList.add( 'modal-open' );

		const { findByText } = render( <TestComponent /> );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( await findByText( 'closed' ) ).toBeTruthy();

		document.body.classList.remove( 'modal-open' );
		expect( await findByText( 'open' ) ).toBeTruthy();
	} );

	it( 'Should be closed when wizard is done', async () => {
		const { queryByText } = render( <TestComponent /> );

		fireEvent.click( queryByText( 'done' ) );

		// Initializes initial state.
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( queryByText( 'closed' ) ).toBeTruthy();
	} );
} );
