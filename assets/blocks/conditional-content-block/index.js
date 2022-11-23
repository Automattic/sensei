/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './conditional-content-edit';
import save from './conditional-content-save';
import metadata from './block.json';
import icon from '../../icons/conditional-content.svg';

export default {
	...metadata,
	metadata,
	icon,
	edit,
	save,
	transforms: {
		from: [
			{
				type: 'block',
				isMultiBlock: true,
				blocks: [ '*' ],
				__experimentalConvert: ( blocks ) => {
					if (
						blocks.length === 1 &&
						blocks[ 0 ].name === 'sensei-lms/conditional-content'
					) {
						return;
					}

					// The conversion is done by creating a wrapper block and setting the selected blocks as inner blocks.
					const wrapperInnerBlocks = blocks.map( ( block ) => {
						return createBlock(
							block.name,
							block.attributes,
							block.innerBlocks
						);
					} );

					const alignments = [ 'wide', 'full' ];

					// Determine the widest setting of all the blocks to be grouped.
					const widestAlignment = blocks.reduce(
						( result, block ) => {
							const { align } = block.attributes;
							return alignments.indexOf( align ) >
								alignments.indexOf( result )
								? align
								: result;
						},
						undefined
					);

					return createBlock(
						'sensei-lms/conditional-content',
						{
							align: widestAlignment,
						},
						wrapperInnerBlocks
					);
				},
			},
		],
	},
};
