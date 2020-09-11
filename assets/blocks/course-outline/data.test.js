import { convertToBlocks, extractBlocksData } from './data';
import './lesson-block';
import './module-block';

describe( 'convertToBlocks', () => {
	it( 'creates blocks from structure', () => {
		const blocks = convertToBlocks( [
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

		expect( blocks ).toEqual( [
			expect.objectContaining( {
				name: 'sensei-lms/course-outline-module',
				attributes: { title: 'M1', description: 'Module 1' },
				innerBlocks: [
					expect.objectContaining( {
						name: 'sensei-lms/course-outline-lesson',
						attributes: { title: 'M1L1' },
						innerBlocks: [],
						isValid: true,
					} ),
					expect.objectContaining( {
						name: 'sensei-lms/course-outline-lesson',
						attributes: { title: 'M1L2' },
						innerBlocks: [],
						isValid: true,
					} ),
				],
				isValid: true,
			} ),
			expect.objectContaining( {
				name: 'sensei-lms/course-outline-lesson',
				attributes: { title: 'L2' },
				innerBlocks: [],
				isValid: true,
			} ),
		] );
	} );
} );
describe( 'extractBlocksData', () => {
	it( 'creates structure from blocks', () => {
		const data = extractBlocksData( [
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
