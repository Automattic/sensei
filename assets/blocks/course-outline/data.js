import { createBlock } from '@wordpress/blocks';
import { invert } from 'lodash';

/**
 * Course structure data.
 *
 * @global
 * @typedef {[CourseLessonData,CourseModuleData]} CourseStructure
 */
/**
 * @typedef CourseModuleData
 * @param {string}             type        Block type ('module')
 * @param {string?}            title       Module title
 * @param {number?}            id          Module ID
 * @param {string?}            description Module description
 * @param {CourseLessonData[]} lessons     Module lessons
 */
/**
 * @typedef CourseLessonData
 * @param {string}  type  Block type ('lesson')
 * @param {string?} title Lesson title
 * @param {number?} id    Lesson ID
 */

export const blockNames = {
	module: 'sensei-lms/course-outline-module',
	lesson: 'sensei-lms/course-outline-lesson',
};

export const blockTypes = invert( blockNames );

/**
 *
 * Convert course structure to blocks.
 *
 * @param {[CourseLessonData,CourseModuleData]} blockData
 * @return {Object[]} Blocks.
 */
export const convertToBlocks = ( blockData ) =>
	blockData.map( ( { type, lessons, ...block } ) =>
		createBlock(
			blockNames[ type ],
			block,
			lessons ? convertToBlocks( lessons ) : []
		)
	);

/**
 * Convert blocks to course structure.
 *
 * @param {Object[]} blocks Blocks.
 * @return {CourseStructure} Course structure
 */
export const extractStructure = ( blocks ) => {
	const extractBlockData = {
		module: ( block ) => ( {
			lessons: extractStructure( block.innerBlocks ),
		} ),
		lesson: () => ( {} ),
	};

	return blocks
		.map( ( block ) => {
			const type = blockTypes[ block.name ];
			return {
				type,
				className: block.className,
				...block.attributes,
				...extractBlockData[ type ]( block ),
			};
		} )
		.filter( ( block ) => !! block.title );
};
