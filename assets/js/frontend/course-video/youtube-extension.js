/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

/**
 * Initializes the YouTube video block player.
 *
 * @param {HTMLElement} iframe The iframe element of the YouTube video block.
 */
const initYouTubePlayer = ( iframe ) => {
	let onVideoEnd = () => {};
	const player = new YT.Player( iframe, {
		events: {
			onStateChange: ( event ) => {
				const playerStatus = event.data;
				if ( playerStatus === YT.PlayerState.ENDED ) {
					onVideoEnd();
				}
			},
			onReady: () => {
				registerVideo( {
					pauseVideo: player.pauseVideo.bind( player ),
					registerVideoEndHandler: ( cb ) => {
						onVideoEnd = cb;
					},
					url: player.getVideoUrl(),
					blockElement: iframe.closest( 'figure' ),
				} );
			},
		},
	} );
};

// For YouTube extension, we need to make sure both window.load
// and window.YouTubeIframeAPIReady are fired.
let windowLoaded = false;
let youtubeIframeReady = false;
const init = () => {
	if ( windowLoaded && youtubeIframeReady ) {
		document
			.querySelectorAll(
				'.sensei-course-video-container.youtube-extension iframe'
			)
			.forEach( initYouTubePlayer );
	}
};

export const initYouTubeExtension = () => {
	windowLoaded = true;
	init();
};

// onYouTubeIframeAPIReady is called by YouTube iframe API when it is ready.
const previousYouTubeIframeAPIReady =
	window.onYouTubeIframeAPIReady !== undefined
		? window.onYouTubeIframeAPIReady
		: () => {};
window.onYouTubeIframeAPIReady = () => {
	youtubeIframeReady = true;
	init();
	previousYouTubeIframeAPIReady();
};
