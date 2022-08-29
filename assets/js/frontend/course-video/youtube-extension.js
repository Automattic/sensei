/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';
import Player from '../../../shared/helpers/player';

/**
 * Initializes the YouTube video block player.
 *
 * @param {HTMLElement} iframe The iframe element of the YouTube video block.
 */
const initYouTubePlayer = ( iframe ) => {
	const player = new Player( iframe );

	const videoId = iframe.src.split( '?' )[ 0 ].split( '/' ).pop();
	const url = 'https://www.youtube.com/watch?v=' + videoId;

	registerVideo( {
		pauseVideo: () => {
			player.pause();
		},
		registerVideoEndHandler: ( cb ) => {
			player.on( 'ended', cb );
		},
		url,
		blockElement: iframe.closest( 'figure' ),
	} );
};

export const initYouTubeExtension = () => {
	document
		.querySelectorAll( '.wp-block-embed-youtube iframe' )
		.forEach( initYouTubePlayer );
};
