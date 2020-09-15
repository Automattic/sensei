import { createBlock } from '@wordpress/blocks';
import { invert, omit } from 'lodash';

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
 * Create blocks based on the structure, keeping existing block attributes.
 *
 * Matches blocks based on lesson/module ID.
 *
 * @param {CourseStructure} structure
 * @param {Object[]}        blocks       Existing blocks.
 * @param {Object[]}        attributeMap Attributes for inner blocks.
 * @return {Object[]} Updated blocks.
 */
export const syncStructureToBlocks = ( structure, blocks, attributeMap ) => {
	return ( structure || [] ).map( ( item ) => {
		let { type, lessons, ...attributes } = item;
		let block = findBlock( blocks, item );
		if ( item.id ) {
			attributes = {
				...attributes,
				...( attributeMap[ `${ type }-${ item.id }` ] || {} ),
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
				block.innerBlocks,
				attributeMap
			);
		}

		return block;
	} );
};

/**
 * Find the block for a given lesson/module item.
 *
 * @param {Object[]}                            blocks Block.
 * @param {[CourseLessonData,CourseModuleData]} item   Structure item.
 * @return {Object} The block, if found.
 */
const findBlock = ( blocks, { id, type } ) => {
	const compare = ( { name, attributes } ) =>
		id === attributes.id && blockNames[ type ] === name;
	return (
		blocks.find( compare ) ||
		( 'lesson' === type &&
			blocks.reduce(
				( found, block ) => found || block.innerBlocks.find( compare ),
				false
			) )
	);
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

/**
 * Get a map of non-structure attributes for the inner blocks,
 * indexed by post type and ID.
 *
 * @param {CourseStructure} structure Structure extracted from blocks.
 * @return {Object} Block attribute map.
 */
export function getChildBlockAttributes( structure ) {
	if ( ! structure ) return {};

	return structure.reduce( ( result, block ) => {
		if ( block.id )
			result[ `${ block.type }-${ block.id }` ] = omit( block, [
				'title',
				'description',
				'lessons',
				'id',
				'type',
			] );
		return { ...result, ...getChildBlockAttributes( block.lessons ) };
	}, {} );
}

export function applyBlockAttributes( { id, type }, blockAttributes ) {
	return blockAttributes[ `${ type }-${ id }` ];
}
