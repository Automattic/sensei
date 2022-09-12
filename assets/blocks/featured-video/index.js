/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, BlockIcon } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { video as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */

import metadata from './block.json';
const ALLOWED_BLOCKS = [
	'core/embed',
	'core/video',
	'sensei-pro/interactive-video',
];

export default {
	title: __( 'Featured Video', 'sensei-lms' ),
	description: __(
		'Add a featured video to your lesson to highlight the video and make use of our video templates.',
		'sensei-lms'
	),
	...metadata,
	edit: ( { className, clientId } ) => {
		const innerBlockCount = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getBlock( clientId ).innerBlocks
		);

		const appenderToUse = () => {
			if ( innerBlockCount.length < 1 ) {
				return (
					<>
						<div className="sensei-featured-video">
							<BlockIcon icon={ icon } />
							Featured Video
						</div>
						<legend className="sensei-featured-video-legend">
							Add a featured video.
						</legend>
						<InnerBlocks.ButtonBlockAppender />
					</>
				);
			}
			return false;
		};

		return (
			<div className={ className }>
				{
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						renderAppender={ () => appenderToUse() }
					/>
				}
			</div>
		);
	},
	save: () => {
		return <InnerBlocks.Content />;
	},
};

wp.domReady( function () {
	const allowedEmbedBlocks = [
		'vimeo',
		'youtube',
		'videopress',
		'dailymotion',
		'wordpress-tv',
	];
	wp.blocks
		.getBlockVariations( 'core/embed' )
		.forEach( function ( blockVariation ) {
			if ( -1 === allowedEmbedBlocks.indexOf( blockVariation.name ) ) {
				wp.blocks.unregisterBlockVariation(
					'core/embed',
					blockVariation.name
				);
			}
		} );
} );
