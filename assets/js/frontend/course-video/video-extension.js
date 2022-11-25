/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';
import Player from '../../../shared/helpers/player';

/**
 * Initializes the Video block player.
 *
 * @param {HTMLElement} video The video element of the Video block.
 */
const initVideoPlayer = ( video ) => {
	const player = new Player( video );

	registerVideo( {
		registerVideoEndHandler: ( cb ) => {
			player.on( 'ended', cb );
		},
		pauseVideo: () => {
			player.pause();
		},
		url: video.src.split( '?' )[ 0 ],
		blockElement: video.closest( 'figure' ),
	} );
};

export const initVideoExtension = () => {
	document
		.querySelectorAll( '.wp-block-video video' )
		.forEach( initVideoPlayer );
};
