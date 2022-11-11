/**
 * WordPress dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { transforms } from './transforms';

const FEATURED_VIDEO_TEMPLATE = [ [ 'core/video' ] ];
const ALLOWED_BLOCKS = [
	'core/embed',
	'core/video',
	'sensei-pro/interactive-video',
];

export default {
	...metadata,
	metadata,
	example: {
		innerBlocks: [
			{
				name: 'core/image',
				attributes: {
					url: `${ window.sensei?.assetUrl }/images/featured-video-example.png`,
				},
			},
		],
	},
	edit: function EditBlock( { className, clientId } ) {
		const { replaceInnerBlocks, moveBlockToPosition } = useDispatch(
			'core/block-editor'
		);
		const innerBlockCount = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getBlocks( clientId ).length
		);
		const previousBlockCount = useRef( innerBlockCount );
		useEffect( () => {
			if ( previousBlockCount.current > 0 && innerBlockCount === 0 ) {
				replaceInnerBlocks(
					clientId,
					[ createBlock( 'core/video' ) ],
					false
				);
			}
			previousBlockCount.current = innerBlockCount;
		}, [ innerBlockCount, clientId, replaceInnerBlocks ] );

		const { parentBlocks, rootClientId, blockIndex } = useSelect(
			( select ) => {
				const {
					getBlockParents,
					getBlockRootClientId,
					getBlockIndex,
				} = select( 'core/block-editor' );
				return {
					parentBlocks: getBlockParents( clientId ),
					rootClientId: getBlockRootClientId( clientId ),
					blockIndex: getBlockIndex( clientId ),
				};
			},
			[ clientId ]
		);

		// Move Featured Video block to top at top level.
		useEffect( () => {
			if ( parentBlocks?.length || blockIndex ) {
				moveBlockToPosition( clientId, rootClientId, '', 0 );
			}
		}, [
			parentBlocks,
			rootClientId,
			blockIndex,
			moveBlockToPosition,
			clientId,
		] );

		return (
			<div className={ className }>
				{
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ FEATURED_VIDEO_TEMPLATE }
						renderAppender={ false }
					/>
				}
			</div>
		);
	},
	save: () => {
		return <InnerBlocks.Content />;
	},
	transforms,
};
