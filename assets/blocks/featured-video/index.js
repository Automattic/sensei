/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import metadata from './block.json';
const FEATURED_VIDEO_TEMPLATE = [ [ 'core/video' ] ];
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
	edit: function EditBlock( { className, clientId } ) {
		const innerBlockCount = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getBlock( clientId ).innerBlocks
		);
		const appenderToUse = () => {
			if ( innerBlockCount.length < 1 ) {
				return <InnerBlocks.ButtonBlockAppender />;
			}
			return false;
		};
		return (
			<div className={ className }>
				{
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ FEATURED_VIDEO_TEMPLATE }
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
