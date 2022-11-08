/**
 * External dependencies
 */
/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { curry, invert } from 'lodash';

/**
 * Course structure data.
 *
 * @global
 * @typedef {Array.<(CourseLessonData|CourseModuleData)>} CourseStructure
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
 * Create blocks based on the structure, keeping existing block attributes.
 *
 * Matches blocks based on lesson/module ID.
 *
 * @param {CourseStructure} structure
 * @param {Object[]}        blocks    Existing blocks.
 * @return {Object[]} Updated blocks.
 */
export const syncStructureToBlocks = ( structure, blocks ) => {
	return ( structure || [] ).map( ( item ) => {
		let { type, lessons, ...attributes } = item;
		let block = findBlock( blocks, item );
		if ( item.id ) {
			attributes = {
				...attributes,
			};
		}
		if ( ! block ) {
			block = createBlock( blockNames[ type ], attributes );
		} else {
			block.attributes = { ...block.attributes, ...attributes };
		}

		if ( 'module' === type ) {
			block.innerBlocks = syncStructureToBlocks(
				lessons,
				block.innerBlocks
			);
		}

		return block;
	} );
};

/**
 * Check if the block is related to the current course structure CourseLessonData|CourseModuleData
 *
 * @param {block}                             block      Gutenberg Block stored on the editor store
 * @param {CourseLessonData|CourseModuleData} courseData Course Lesson Data or Course Module Data..
 * @return {boolean} returns if the block matches with the data structure.
 */
const byCourseData = curry( ( courseData, block ) => {
	const { name, attributes } = block;
	const isTheCorrectBlockType = Object.keys( blockTypes ).includes( name );

	const findById = () => !! attributes.id && courseData.id === attributes.id;
	const findByTitle = () => attributes.title === courseData.title;
	const findByLastTitle = () => attributes.title === courseData.lastTitle;

	if ( ! isTheCorrectBlockType ) {
		return false;
	}

	return [ findById(), findByTitle(), findByLastTitle() ].includes( true );
} );

const findInInnerBlocks = ( blocks, predicate ) =>
	blocks.reduce(
		( found, block ) => found || block.innerBlocks.find( predicate ),
		false
	);

/**
 * Find the block for a given lesson/module item.
 *
 * @param {Object[]}                                    blocks     Block.
 * @param {Array.<(CourseLessonData|CourseModuleData)>} courseData Course item.
 * @return {Object} The block, if found.
 */
const findBlock = ( blocks, courseData ) => {
	const isLesson = ( value ) => value === 'lesson';
	const found = blocks.find( byCourseData( courseData ) );

	if ( ! found && isLesson( courseData.type ) ) {
		return findInInnerBlocks( blocks, byCourseData( courseData ) );
	}

	return found;
};
/**
 * Convert blocks to course structure.
 *
 * @param {Object[]} blocks Blocks.
 * @return {CourseStructure} Course structure
 */
export const extractStructure = ( blocks ) => {
	const extractBlockData = {
		module: ( block ) => ( {
			description: block.attributes.description,
			lessons: extractStructure( block.innerBlocks ),
			teacher: block.attributes.teacher,
			teacherId: block.attributes.teacherId,
			lastTitle: block.attributes.lastTitle,
			slug: block.attributes.slug,
		} ),
		lesson: ( block ) => ( {
			draft: block.attributes.draft,
			preview: block.attributes.preview,
		} ),
	};

	return blocks
		.map( ( block ) => {
			const type = blockTypes[ block.name ];
			return {
				type,
				id: block.attributes.id,
				title: block.attributes.title,
				...extractBlockData[ type ]( block ),
			};
		} )
		.filter( ( block ) => 'module' === block.type || !! block.title );
};

/**
 * Get first block by name.
 *
 * @param {string} blockName Block name.
 * @param {Array}  blocks    Blocks array.
 *
 * @return {Object|boolean} Block or false.
 */
export const getFirstBlockByName = ( blockName, blocks ) => {
	for ( let i = 0; i < blocks.length; i++ ) {
		const block = blocks[ i ];
		if ( blockName === block.name ) {
			return block;
		} else if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			const innerBlockSearch = getFirstBlockByName(
				blockName,
				block.innerBlocks
			);
			if ( innerBlockSearch ) {
				return innerBlockSearch;
			}
		}
	}

	return false;
};

/**
 * Get the course outline inner blocks of a specific type.
 *
 * @param {string} outlineClientId The outline block client id.
 * @param {string} blockType       The block type to return.
 *
 * @return {Array} An array of blocks.
 */
export const getCourseInnerBlocks = ( outlineClientId, blockType ) => {
	let allChildren = select( 'core/block-editor' ).getBlocks(
		outlineClientId
	);

	allChildren = allChildren.reduce(
		( m, block ) => [ ...m, ...block.innerBlocks ],
		allChildren
	);

	return allChildren.filter( ( { name } ) => blockType === name );
};
