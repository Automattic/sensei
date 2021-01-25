/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Module block transform.
 */
export default {
	from: [
		{
			type: 'block',
			blocks: [ 'sensei-lms/course-outline-lesson' ],
			isMultiBlock: true,
			/**
			 * Group selected lesson blocks into a module.
			 *
			 * @param {Object[]} blocks Attributes of the selected blocks.
			 */
			transform( blocks ) {
				const innerBlocks = blocks.map( ( block ) => {
					return createBlock(
						'sensei-lms/course-outline-lesson',
						block
					);
				} );

				return createBlock(
					'sensei-lms/course-outline-module',
					{},
					innerBlocks
				);
			},
		},
	],
};
