/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

/**
 * Initializes the Video block player.
 *
 * @param {HTMLElement} video The video element of the Video block.
 */
const initVideoPlayer = ( video ) => {
	let onVideoEnd = () => {};
	video.addEventListener( 'ended', () => {
		onVideoEnd();
	} );

	registerVideo( {
		registerVideoEndHandler: ( cb ) => {
			onVideoEnd = cb;
		},
		pauseVideo: video.pause.bind( video ),
		url: video.src.split( '?' )[ 0 ],
		blockElement: video.closest( 'figure' ),
	} );
};

export const initVideoExtension = () => {
	document
		.querySelectorAll( '.sensei-course-video-container video' )
		.forEach( initVideoPlayer );
};
