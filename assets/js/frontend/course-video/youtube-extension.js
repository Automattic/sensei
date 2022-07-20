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

	const player = YT.get( iframe.id ) || new YT.Player( iframe );

	const onReady = () => {
		registerVideo( {
			pauseVideo: player.pauseVideo.bind( player ),
			registerVideoEndHandler: ( cb ) => {
				onVideoEnd = cb;
			},
			url: player.getVideoUrl(),
			blockElement: iframe.closest( 'figure' ),
		} );
	};

	if ( player.getDuration ) {
		// Just in case it's called after the player is ready.
		onReady();
	} else {
		player.addEventListener( 'onReady', onReady );
	}

	player.addEventListener( 'onStateChange', ( event ) => {
		const playerStatus = event.data;
		if ( playerStatus === YT.PlayerState.ENDED ) {
			onVideoEnd();
		}
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

window.senseiYouTubeIframeAPIReady.then( () => {
	youtubeIframeReady = true;
	init();
} );
