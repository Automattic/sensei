/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

/**
 * Initializes Vimeo block video player.
 *
 * @param {HTMLElement} iframe The iframe element of the Vimeo video block.
 */
const initVimeoPlayer = ( iframe ) => {
	let onVideoEnd = () => {};
	const player = new Vimeo.Player( iframe );
	player.on( 'ended', () => {
		onVideoEnd();
	} );

	player.getVideoUrl().then( ( url ) => {
		registerVideo( {
			registerVideoEndHandler: ( cb ) => {
				onVideoEnd = cb;
			},
			pauseVideo: player.pause.bind( player ),
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
