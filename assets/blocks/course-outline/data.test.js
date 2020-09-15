import { createBlock } from '@wordpress/blocks';
import { extractStructure, syncStructureToBlocks } from './data';
import './lesson-block';
import './module-block';

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

		const newBlocks = syncStructureToBlocks( changed, blocks, [] );

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
} );
