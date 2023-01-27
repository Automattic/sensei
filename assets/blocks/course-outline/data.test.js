/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	extractStructure,
	syncStructureToBlocks,
	getFirstBlockByName,
} from './data';
import {
	registerTestLessonBlock,
	registerTestModuleBlock,
} from './test-helpers';

registerTestLessonBlock();
registerTestModuleBlock();

describe( 'extractStructure', () => {
	it( 'creates structure from blocks', () => {
		const data = extractStructure( [
			{
				name: 'sensei-lms/course-outline-module',
				attributes: { title: 'M1', description: 'Module 1' },
				innerBlocks: [
					{
						name: 'sensei-lms/course-outline-lesson',
						attributes: { title: 'M1L1' },
						innerBlocks: [],
						isValid: true,
					},
					{
						name: 'sensei-lms/course-outline-lesson',
						attributes: { title: 'M1L2' },
						innerBlocks: [],
						isValid: true,
					},
				],
				isValid: true,
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: { title: 'L2' },
				innerBlocks: [],
				isValid: true,
			},
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: { title: 'L3', draft: true, preview: true },
				innerBlocks: [],
				isValid: true,
			},
		] );

		expect( data ).toEqual( [
			{
				type: 'module',
				description: 'Module 1',
				title: 'M1',
				lessons: [
					{ type: 'lesson', title: 'M1L1' },
					{ type: 'lesson', title: 'M1L2' },
				],
			},
			{ type: 'lesson', title: 'L2' },
			{ type: 'lesson', title: 'L3', draft: true, preview: true },
		] );
	} );
} );

describe( 'syncStructureToBlocks', () => {
	it( 'merges with existing blocks', () => {
		const blocks = [
			createBlock(
				'sensei-lms/course-outline-module',
				{
					title: 'M1',
					description: 'Module 1',
					id: 1,
					style: { color: 'red' },
				},
				[
					createBlock( 'sensei-lms/course-outline-lesson', {
						title: 'M1L1',
						id: 3,
						style: { color: 'red' },
					} ),
					createBlock( 'sensei-lms/course-outline-lesson', {
						title: 'M1L3 New',
						style: { color: 'red' },
					} ),
					createBlock( 'sensei-lms/course-outline-lesson', {
						title: 'M1L4 Removed',
						id: 5,
					} ),
				]
			),
			createBlock( 'sensei-lms/course-outline-lesson', {
				title: 'L2',
				id: 8,
				style: { color: 'red' },
			} ),
		];

		const changed = [
			{ type: 'lesson', title: 'L2', id: 8 },
			{
				type: 'module',
				description: 'Module 1',
				id: 1,
				title: 'M1',
				lessons: [
					{ type: 'lesson', title: 'M1L2', id: 4 },
					{ type: 'lesson', title: 'M1L1', id: 3 },
				],
			},
		];

		const newBlocks = syncStructureToBlocks( changed, blocks );

		expect( newBlocks ).toEqual( [
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: { title: 'L2', id: 8, style: { color: 'red' } },
				innerBlocks: [],
				clientId: blocks[ 1 ].clientId,
				isValid: true,
			},
			{
				name: 'sensei-lms/course-outline-module',
				attributes: {
					title: 'M1',
					id: 1,
					description: 'Module 1',
					style: { color: 'red' },
				},
				clientId: blocks[ 0 ].clientId,
				isValid: true,
				innerBlocks: [
					{
						name: 'sensei-lms/course-outline-lesson',
						attributes: {
							title: 'M1L2',
							id: 4,
							style: {},
						},
						innerBlocks: [],
						isValid: true,
						clientId: expect.anything(),
					},
					{
						name: 'sensei-lms/course-outline-lesson',
						attributes: {
							title: 'M1L1',
							id: 3,
							style: { color: 'red' },
						},
						innerBlocks: [],
						clientId: blocks[ 0 ].innerBlocks[ 1 ].clientId,
						isValid: true,
					},
				],
			},
		] );
	} );

	it( 'merges with existing blocks using the id', () => {
		const blocks = [
			createBlock( 'sensei-lms/course-outline-module', {
				title: 'L1',
				type: 'lesson',
				description: 'lesson 1',
				id: 1,
				style: { color: 'red' },
			} ),
		];

		const changed = [
			{
				description: 'Lesson 1 description updated',
				type: 'lesson',
				title: 'L1 updated',
				id: 1,
			},
		];

		const newBlocks = syncStructureToBlocks( changed, blocks );

		expect( newBlocks ).toEqual( [
			{
				clientId: blocks[ 0 ].clientId,
				name: 'sensei-lms/course-outline-module',
				attributes: {
					title: 'L1 updated',
					description: 'Lesson 1 description updated',
					id: 1,
					style: { color: 'red' },
				},
				innerBlocks: [],
				isValid: true,
			},
		] );
	} );

	it( 'merges with existing blocks using the title when there is not id', () => {
		const blocks = [
			createBlock( 'sensei-lms/course-outline-module', {
				title: 'M1',
				type: 'module',
				description: 'Module 1',
				style: { color: 'red' },
			} ),
		];

		const changed = [
			{
				description: 'Module 1 description updated',
				type: 'module',
				title: 'M1',
				id: 2,
			},
		];

		const newBlocks = syncStructureToBlocks( changed, blocks );

		expect( newBlocks ).toEqual( [
			{
				clientId: blocks[ 0 ].clientId,
				name: 'sensei-lms/course-outline-module',
				attributes: {
					title: 'M1',
					description: 'Module 1 description updated',
					id: 2,
					style: { color: 'red' },
				},
				innerBlocks: [],
				isValid: true,
			},
		] );
	} );

	it( 'merges with existing blocks using the lastTitle', () => {
		const blocks = [
			createBlock( 'sensei-lms/course-outline-module', {
				title: 'M1',
				type: 'module',
				description: 'Module 1',
				id: 1,
				style: { color: 'red' },
			} ),
		];

		const changed = [
			{
				description: 'Module 1 description updated',
				type: 'module',
				title: 'M1 updated',
				lastTitle: 'M1',
				id: 2,
			},
		];

		const newBlocks = syncStructureToBlocks( changed, blocks );

		expect( newBlocks ).toEqual( [
			{
				clientId: blocks[ 0 ].clientId,
				name: 'sensei-lms/course-outline-module',
				attributes: {
					title: 'M1 updated',
					lastTitle: 'M1',
					description: 'Module 1 description updated',
					id: 2,
					style: { color: 'red' },
				},
				innerBlocks: [],
				isValid: true,
			},
		] );
	} );

	it( 'merges with existing blocks looking on inner blocks', () => {
		const innerId = 99;
		const innerBlock = createBlock( 'sensei-lms/course-outline-lesson', {
			title: 'lesson 1 title',
			type: 'lesson',
			description: 'Lesson 1',
			id: innerId,
			style: { color: 'blue' },
		} );

		const blocks = [
			createBlock(
				'sensei-lms/course-outline-module',
				{
					title: 'M1',
					type: 'module',
					description: 'Module 1',
					id: 1,
					style: { color: 'red' },
				},
				[ innerBlock ]
			),
		];

		const changed = [
			{
				description: 'Lesson 1 description updated',
				type: 'lesson',
				title: 'Lesson 1 title updated',
				id: innerId,
			},
		];

		const newBlocks = syncStructureToBlocks( changed, blocks );

		expect( newBlocks[ 0 ] ).toEqual( {
			clientId: innerBlock.clientId,
			name: 'sensei-lms/course-outline-lesson',
			attributes: {
				title: 'Lesson 1 title updated',
				description: 'Lesson 1 description updated',
				id: 99,
				style: { color: 'blue' },
			},
			innerBlocks: [],
			isValid: true,
		} );
	} );
} );

describe( 'getFirstBlockByName', () => {
	it( 'should get the first block with correct name', () => {
		const blocks = [
			{ name: 'a', innerBlocks: [] },
			{
				name: 'b',
				innerBlocks: [ { name: 'f' }, { name: 'g' } ],
			},
			{
				name: 'c',
				innerBlocks: [
					{ name: 'h' },
					{
						name: 'i',
						innerBlocks: [
							{ name: 'j' },
							{ name: 'wally' },
							{ name: 'k' },
						],
					},
				],
			},
			{ name: 'd' },
			{ name: 'e', innerBlocks: [] },
		];
		expect( getFirstBlockByName( 'wally', blocks ).name ).toEqual(
			'wally'
		);
	} );
} );
