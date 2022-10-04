/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import blockMeta from './block.json';

export const transforms = {
	from: [ 'core/video', 'core/embed', 'sensei-pro/interactive-video' ].map(
		( blockName ) => ( {
			type: 'block',
			blocks: [ blockName ],
			transform: ( attributes = {}, innerBlocks = [] ) => {
				let name = blockName;
				if ( 'core/embed' === name && ! attributes.providerNameSlug ) {
					name = 'core/video';
				}
				return createBlock( blockMeta.name, {}, [
					createBlock( name, attributes, innerBlocks ),
				] );
			},
		} )
	),

	to: [ 'core/video', 'core/embed', 'sensei-pro/interactive-video' ].map(
		( blockName ) => ( {
			type: 'block',

			blocks: [ blockName ],

			isMatch: ( _, block = {} ) =>
				blockName === block.innerBlocks?.[ 0 ]?.name,

			transform: ( _, inner = [] ) => {
				const { attributes = {}, innerBlocks = [] } = inner[ 0 ] || {};
				return createBlock( blockName, attributes, innerBlocks );
			},
		} )
	),
};
