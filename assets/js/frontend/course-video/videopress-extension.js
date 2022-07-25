/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

/**
 * Extracts the video id from the url of the video.
 *
 * @param {string} url The url of the video.
 * @return {string} The id of the video.
 */
const extractVideoPressIdFromUrl = ( url ) => {
	const urlWithoutQuery = url.split( '?' )[ 0 ];
	const parts = urlWithoutQuery.split( '/' );
	return parts[ parts.length - 1 ];
};

/**
 * Initializes the VideoPress block player.
 *
 * @param {HTMLIFrameElement} iframe The iframe of the VideoPress block.
 */
const initVideoPressPlayer = ( iframe ) => {
	const videoId = extractVideoPressIdFromUrl( iframe.src );
	let onVideoEnd = () => {};

	// eslint-disable-next-line @wordpress/no-global-event-listener
	window.addEventListener(
		'message',
		( event ) => {
			if ( event.source !== iframe.contentWindow ) {
				return;
			}
			if (
				event.data.event === 'videopress_ended' &&
				event.data.id === videoId
			) {
				onVideoEnd();
			}
		},
		false
	);

	registerVideo( {
		registerVideoEndHandler: ( cb ) => {
			onVideoEnd = cb;
		},
		pauseVideo: () => {
			iframe.contentWindow.postMessage(
				{
					event: 'videopress_action_pause',
				},
				'*'
			);
		},
		url: iframe.src.split( '?' )[ 0 ],
		blockElement: iframe.closest( 'figure' ),
	} );
};

export const initVideoPressExtension = () => {
	document
		.querySelectorAll(
			'.sensei-course-video-container.videopress-extension iframe'
		)
		.forEach( initVideoPressPlayer );
};
