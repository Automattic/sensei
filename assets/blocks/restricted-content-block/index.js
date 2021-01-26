/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './restricted-content-edit';
import save from './restricted-content-save';
import metadata from './block';

export default {
	title: __( 'Restricted Course Content', 'sensei-lms' ),
	description: __(
		'Display conditional course content to users.',
		'sensei-lms'
	),
	keywords: [
		__( 'Enrolled', 'sensei-lms' ),
		__( 'Content', 'sensei-lms' ),
		__( 'Locked', 'sensei-lms' ),
		__( 'Private', 'sensei-lms' ),
		__( 'Completed', 'sensei-lms' ),
		__( 'Not Enrolled', 'sensei-lms' ),
		__( 'Restricted', 'sensei-lms' ),
	],
	icon: () => <Icon icon="lock" />,
	edit,
	save,
	...metadata,
	transforms: {
		from: [
			{
				type: 'block',
				isMultiBlock: true,
				blocks: [ '*' ],
				__experimentalConvert: ( blocks ) => {
					if (
						blocks.length === 1 &&
						blocks[ 0 ].name === 'sensei-lms/restricted-content'
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
						'sensei-lms/restricted-content',
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
