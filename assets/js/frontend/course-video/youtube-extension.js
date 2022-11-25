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

	// iframe.src should be in the format:
	// https://www.youtube.com/embed/VIDEO_ID?other-query-parameters=and-their-values&origin=https://example.com
	const videoId = iframe.src.split( '?' )[ 0 ].split( '/' ).pop();
	// We compute the URL like this to allow backward compatibility with the value returned from
	// nativeYoutubePlayer.getVideoUrl() - so we just add the videoId to the prefix used by YouTube for videos.
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
