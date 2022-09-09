/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';
import Player from '../../../shared/helpers/player';

/**
 * Initializes the VideoPress block player.
 *
 * @param {HTMLIFrameElement} iframe The iframe of the VideoPress block.
 */
const initVideoPressPlayer = ( iframe ) => {
	const player = new Player( iframe );

	registerVideo( {
		registerVideoEndHandler: ( cb ) => {
			player.on( 'ended', cb );
		},
		pauseVideo: () => {
			player.pause();
		},
		url: iframe.src.split( '?' )[ 0 ],
		blockElement: iframe.closest( 'figure' ),
	} );
};

export const initVideoPressExtension = () => {
	document
		.querySelectorAll( '.wp-block-embed-videopress iframe' )
		.forEach( initVideoPressPlayer );
};
