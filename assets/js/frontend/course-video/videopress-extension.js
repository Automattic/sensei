/**
 * Internal dependencies
 */
import { registerVideo } from './video-blocks-manager';

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
