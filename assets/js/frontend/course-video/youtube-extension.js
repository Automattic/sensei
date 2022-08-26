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

	player.getPlayer().then( ( nativeYoutubePlayer ) => {
		registerVideo( {
			pauseVideo: () => {
				player.pause();
			},
			registerVideoEndHandler: ( cb ) => {
				player.on( 'ended', cb );
			},
			url: nativeYoutubePlayer.getVideoUrl(),
			blockElement: iframe.closest( 'figure' ),
		} );
	} );
};

export const initYouTubeExtension = () => {
	document
		.querySelectorAll( '.wp-block-embed-youtube iframe' )
		.forEach( initYouTubePlayer );
};
