/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

import {
	InnerBlocks,
	BlockIcon,
	MediaPlaceholder,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { video as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { createUpgradedEmbedBlock } from './embed-util';
import metadata from './block.json';

export default {
	title: __( 'Featured Video', 'sensei-lms' ),
	description: __(
		'Add a featured video to your lesson to highlight the video and make use of our video templates.',
		'sensei-lms'
	),
	...metadata,
	edit: function EditVideoBlock( { clientId } ) {
		const videoBlock = useSelect( ( select ) =>
			select( 'core/block-editor' ).getBlocks( clientId )
		)?.[ 0 ];

		const { insertBlock } = useDispatch( blockEditorStore );

		const addVideoBlock = ( attrs ) => {
			const newVideoBlock = createBlock( 'core/video', attrs, [] );
			insertBlock( newVideoBlock, 0, clientId, true );
		};

		const onSelectVideo = ( media ) => {
			if ( ! media || ! media.url ) {
				return;
			}

			addVideoBlock( {
				src: media.url,
				id: media.id,
				poster:
					media.image?.src !== media.icon
						? media.image?.src
						: undefined,
			} );
		};

		const onSelectURL = ( src ) => {
			// Check if there's an embed block that handles this URL.
			const embedBlock = createUpgradedEmbedBlock( {
				attributes: { url: src },
			} );
			if ( embedBlock ) {
				insertBlock( embedBlock, 0, clientId, true );
			} else {
				addVideoBlock( { src, id: undefined, poster: undefined } );
			}
		};

		const innerBlockProps = useMemo(
			() =>
				videoBlock
					? { template: [ videoBlock.name ], templateLock: true }
					: {},
			[ videoBlock ]
		);

		return (
			<>
				<InnerBlocks { ...innerBlockProps } renderAppender={ false } />
				{ ! videoBlock && (
					<MediaPlaceholder
						icon={ <BlockIcon icon={ icon } /> }
						labels={ {
							title: __( 'Featured Video', 'sensei-lms' ),
						} }
						onSelect={ onSelectVideo }
						onSelectURL={ onSelectURL }
						accept="video/*"
						allowedTypes={ [ 'video' ] }
					/>
				) }
			</>
		);
	},
	save: () => {
		return <InnerBlocks.Content />;
	},
};
