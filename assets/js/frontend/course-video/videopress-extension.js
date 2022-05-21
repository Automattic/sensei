/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

/**
 * Initializes the VideoPress block player.
 *
 * @param {HTMLElement} iframe The iframe of the VideoPress block.
 */
const initVideoPressPlayer = ( iframe ) => {
	const videoId = extractVideoPressIdFromUrl( iframe.src );
	let onVideoEnd = () => {};

	iframe.addEventListener( 'load', () => {
		// eslint-disable-next-line @wordpress/no-global-event-listener
		window.addEventListener(
			'message',
			( event ) => {
				if ( event.source !== iframe.contentWindow ) {
					return;
				}
				if (
					event.data.event === 'ended' &&
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
			url: iframe.src,
			blockElement: iframe.closest( 'figure' ),
		} );
	} );
};

export const initVideoPressExtension = () => {
	document
		.querySelectorAll(
			'.sensei-course-video-container.videopress-extension iframe'
		)
		.forEach( initVideoPressPlayer );
};
