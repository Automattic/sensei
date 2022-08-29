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

	const videoId = iframe.src.split( '?' )[ 0 ].split( '/' ).pop();
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
