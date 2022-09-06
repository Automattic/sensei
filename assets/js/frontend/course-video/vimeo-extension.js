/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';
import Player from '../../../shared/helpers/player';

/**
 * Initializes Vimeo block video player.
 *
 * @param {HTMLElement} iframe The iframe element of the Vimeo video block.
 */
const initVimeoPlayer = ( iframe ) => {
	const player = new Player( iframe );

	// iframe.src should be in the format:
	// https://player.vimeo.com/video/VIDEO_ID?other-query-parameters=and-their-values
	const videoId = iframe.src.split( '?' )[ 0 ].split( '/' ).pop();
	// We compute the URL like this to allow backward compatibility with the value returned from
	// nativeVimeoPlayer.getVideoUrl() - so we just add the videoId to the prefix used by Vimeo for videos.
	const url = 'https://vimeo.com/' + videoId;

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

export const initVimeoExtension = () => {
	document
		.querySelectorAll( '.wp-block-embed-vimeo iframe' )
		.forEach( initVimeoPlayer );
};
