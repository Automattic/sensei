/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

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
		url: video.src,
	} );
};

export const initVideoExtension = () => {
	document
		.querySelectorAll( '.sensei-course-video-container video' )
		.forEach( initVideoPlayer );
};
