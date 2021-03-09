/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { renderToString } from '@wordpress/element';

/**
 * External dependencies
 */
import { omit } from 'lodash';

/**
 * Internal dependencies
 */
import metadata from './block.json';

/**
 * Helper method to get a related block for each type of media.
 *
 * @param {Object}   media       Question media.
 * @param {number}   media.id    Media attachment id.
 * @param {string}   media.url   Media attachment url.
 * @param {string}   media.type  Media attachment type.
 * @param {Function} media.title Media attachment title.
 */
const getMediaBlock = ( media ) => {
	switch ( media.type ) {
		case 'image':
			return createBlock( 'core/image', {
				id: media.id,
				url: media.url,
			} );
		case 'audio':
			return createBlock( 'core/audio', {
				id: media.id,
				src: media.url,
			} );
		case 'video':
			return createBlock( 'core/video', {
				id: media.id,
				src: media.url,
			} );
		default:
			const link = <a href={ media.url }>{ media.title }</a>;
			return createBlock( 'core/paragraph', {
				content: renderToString( link ),
			} );
	}
};

export default [
	{
		onProgrammaticCreation: true,
		isEligible( props ) {
			return !! props.media;
		},
		attributes: {
			...metadata.attributes,
			media: {
				type: 'object',
			},
		},
		migrate( attributes, innerBlocks ) {
			return [
				omit( attributes, 'media' ),
				[ ...innerBlocks, getMediaBlock( attributes.media ) ],
			];
		},
		save() {
			return <InnerBlocks.Content />;
		},
	},
	{
		onProgrammaticCreation: true,
		isEligible( props ) {
			return (
				props.type === 'file-upload' && !! props.options?.studentHelp
			);
		},
		attributes: metadata.attributes,
		migrate( attributes, innerBlocks ) {
			return [
				{
					...attributes,
					options: omit( attributes.options, 'studentHelp' ),
				},
				[
					...innerBlocks,
					createBlock( 'core/paragraph', {
						content: attributes.options.studentHelp,
					} ),
				],
			];
		},
		save() {
			return <InnerBlocks.Content />;
		},
	},
];
