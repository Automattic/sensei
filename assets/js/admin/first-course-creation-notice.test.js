/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { dispatch, select, subscribe } from '@wordpress/data';
/**
 * Internal dependencies
 */
import {
	hasOutlineBlock,
	handleCourseOutlineBlockIncomplete,
	handleFirstCourseCreationHelperNotice,
	hasLessonInOutline,
} from './first-course-creation-notice';
import { getFirstBlockByName } from '../../blocks/course-outline/data';

// Initial mocks.
jest.mock( '@wordpress/blocks', () => ( {
	createBlock: jest.fn().mockImplementation( () => ( {
		attributes: {},
		clientId: 'new-block-id',
	} ) ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockImplementation( () => ( {
		createInfoNotice: jest.fn(),
		removeNotice: jest.fn(),
		insertBlock: jest.fn(),
		selectBlock: jest.fn(),
	} ) ),
	select: jest.fn().mockImplementation( () => ( {
		getCurrentUser: jest.fn(),
		getBlocks: jest.fn(),
	} ) ),
	subscribe: jest.fn(),
	use: jest.fn(),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	...jest.requireActual( '@wordpress/i18n' ),
	__: jest.fn(),
} ) );

jest.mock( './first-course-creation-notice' );
jest.mock( '../../blocks/course-outline/data' );

describe( 'hasPublishedLessonInOutline', () => {
	it( 'should return true when there is a lesson in the outline', () => {
		const blocks = [
			{
				name: 'sensei-lms/course-outline-lesson',
			},
		];

		hasLessonInOutline.mockImplementation(
			jest.requireActual( './first-course-creation-notice' )
				.hasPublishedLessonInOutline
		);

		const result = hasLessonInOutline( blocks );

		expect( result ).toBe( true );
	} );

	it( 'should return false when there is no lesson in the outline', () => {
		const blocks = [ { name: 'some-other-block' } ];

		const result = hasLessonInOutline( blocks );

		expect( result ).toBe( false );
	} );
} );

describe( 'handleCourseOutlineBlockIncomplete', () => {
	beforeEach( () => {
		handleCourseOutlineBlockIncomplete.mockImplementation(
			jest.requireActual( './first-course-creation-notice' )
				.handleCourseOutlineBlockIncomplete
		);
		getFirstBlockByName.mockClear();
		hasOutlineBlock.mockClear();
		createBlock.mockClear();
	} );
	it( 'should create and insert a block when no course outline block exists', () => {
		// Mock hasOutlineBlock to return falsy.
		getFirstBlockByName.mockImplementation( () => null );
		const mockInsertBlock = jest.fn();
		dispatch.mockImplementation( () => ( {
			insertBlock: mockInsertBlock,
			selectBlock: jest.fn(),
		} ) );

		handleCourseOutlineBlockIncomplete();

		// Ensure createBlock and insertBlock were called with the correct parameters.
		expect( createBlock ).toHaveBeenCalledWith(
			'sensei-lms/course-outline'
		);
		expect( mockInsertBlock ).toHaveBeenCalled();
	} );

	it( 'should focus on the existing course outline block when it exists', () => {
		// Mock hasOutlineBlock to return a truthy value.
		getFirstBlockByName.mockImplementation( () => ( {
			clientId: 'existing-block-id',
		} ) );
		const mockInsertBlock = jest.fn();
		const mockSelectBlock = jest.fn();
		dispatch.mockImplementation( () => ( {
			insertBlock: mockInsertBlock,
			selectBlock: mockSelectBlock,
		} ) );

		handleCourseOutlineBlockIncomplete();

		// Ensure selectBlock was called with the correct parameters.
		expect( mockSelectBlock ).toHaveBeenCalledWith( 'existing-block-id' );
		// Ensure createBlock and insertBlock were not called.
		expect( createBlock ).not.toHaveBeenCalled();
		expect( mockInsertBlock ).not.toHaveBeenCalled();
	} );
} );
