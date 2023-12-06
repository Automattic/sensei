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
	hasPublishedLessonInOutline,
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
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	...jest.requireActual( '@wordpress/i18n' ),
	__: jest.fn(),
} ) );

jest.mock( './first-course-creation-notice' );
jest.mock( '../../blocks/course-outline/data' );

describe( 'hasPublishedLessonInOutline', () => {
	it( 'should return true when there is a published lesson in the outline', () => {
		const blocks = [
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: { draft: false },
			},
		];

		hasPublishedLessonInOutline.mockImplementation(
			jest.requireActual( './first-course-creation-notice' )
				.hasPublishedLessonInOutline
		);

		const result = hasPublishedLessonInOutline( blocks );

		expect( result ).toBe( true );
	} );

	it( 'should return false when there is no published lesson in the outline', () => {
		const blocks = [ { name: 'some-other-block' } ];

		const result = hasPublishedLessonInOutline( blocks );

		expect( result ).toBe( false );
	} );
} );
