/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
/**
 * External dependencies
 */
import { useRef, useEffect } from 'react';

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
		const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
		const innerBlockCount = useSelect(
			( select ) =>
				select( 'core/block-editor' ).getBlock( clientId ).innerBlocks
		);
		const previousBlockCount = useRef( innerBlockCount );
		useEffect( () => {
			if (
				previousBlockCount.current.length > 0 &&
				innerBlockCount.length === 0
			) {
				replaceInnerBlocks(
					clientId,
					[ createBlock( 'core/video' ) ],
					false
				);
			}
			previousBlockCount.current = innerBlockCount;
		}, [ innerBlockCount, clientId, replaceInnerBlocks ] );
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
};
