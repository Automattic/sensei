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

	player
		.getPlayer()
		.then( ( nativeVimeoPlayer ) => nativeVimeoPlayer.getVideoUrl() )
		.then( ( url ) => {
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
		} );
};

export const initVimeoExtension = () => {
	document
		.querySelectorAll(
			'.sensei-course-video-container.vimeo-extension iframe'
		)
		.forEach( initVimeoPlayer );
};
